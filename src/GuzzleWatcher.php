<?php

namespace Webong\GuzzleTelescopeMiddleware;

use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Response;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Watchers\Watcher;
use Webong\GuzzleTelescopeMiddleware\GuzzleHandled;

class GuzzleWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(GuzzleHandled::class, [$this, 'recordRequest']);
    }

    /**
     * Record a Guzzle HTTP request.
     *
     * @param \Webong\GuzzleTelescope\GuzzleHandled $event
     * @return void
     */
    public function recordRequest(GuzzleHandled $event)
    {
        if (!Telescope::isRecording()) {
            return;
        }

        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');

        Telescope::recordRequest(
            IncomingEntry::make([
                'uri' => $event->request->getUri(),
                'method' => $event->request->getMethod(),
                'headers' => $this->headers($event->request->getHeaders()),
                'payload' => $this->payload($event->request->getBody()->getContent()),
                'response_status' => $event->response->getStatusCode(),
                'response' => $this->response($event->response),
                'reason' => $event->response->getReasonPhrase(),
                'error' => $event->error ?? null,
                'options' => $event->options,
                'duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
                // 'memory' => round(memory_get_peak_usage(true) / 1024 / 1025, 1),
            ])->tags($this->tags())
        );
    }


    /**
     * Retuen tags for the given event.
     *
     * @return array
     */
    private function tags()
    {
        return ['guzzle'];
    }

    /**
     * Format the given headers.
     *
     * @param  array  $headers
     * @return array
     */
    protected function headers($headers)
    {
        $headers = collect($headers)->map(function ($header) {
            return $header[0];
        })->toArray();

        return $this->hideParameters(
            $headers,
            Telescope::$hiddenRequestHeaders
        );
    }

    /**
     * Format the given payload.
     *
     * @param  array  $payload
     * @return array
     */
    protected function payload($payload)
    {
        return $this->hideParameters(
            $payload,
            Telescope::$hiddenRequestParameters
        );
    }

    /**
     * Hide the given parameters.
     *
     * @param  array  $data
     * @param  array  $hidden
     * @return mixed
     */
    protected function hideParameters($data, $hidden)
    {
        foreach ($hidden as $parameter) {
            if (Arr::get($data, $parameter)) {
                Arr::set($data, $parameter, '********');
            }
        }

        return $data;
    }

    /**
     * Get a short summary of the response
     *
     * Will return `null` if the response is not printable.
     *
     * @param Response $response
     *
     * @return string|null
     */
    public static function response(Response $response)
    {
        $body = $response->getBody();

        if (!$body->isSeekable()) {
            return null;
        }

        $size = $body->getSize();

        if ($size === 0) {
            return null;
        }

        $summary = $body->read(120);
        $body->rewind();

        if ($size > 120) {
            $summary .= ' (truncated...)';
        }

        // Matches any printable character, including unicode characters:
        // letters, marks, numbers, punctuation, spacing, and separators.
        if (preg_match('/[^\pL\pM\pN\pP\pS\pZ\n\r\t]/', $summary)) {
            return null;
        }

        return $summary;
    }
}
