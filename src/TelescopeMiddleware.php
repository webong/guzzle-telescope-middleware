<?php
namespace Webong\GuzzleTelescopeMiddleware;

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Exception\RequestException;

class TelescopeMiddleware
{
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $handler($request, $options)->then(
                function ($response) use ($request, $options) {
                    app('events')->dispatch(
                        new GuzzleHandled($request, $response, $options)
                    );
                    return $response;
                },
                function ($reason) use ($request, $options) {
                    $response = $reason instanceof RequestException
                    ? $reason->getResponse()
                    : null;
                    app('events')->dispatch(
                        new GuzzleHandled($request, $response, $options, $reason)
                    );
                    return \GuzzleHttp\Promise\rejection_for($reason);
                }
            );
        };
    }
}