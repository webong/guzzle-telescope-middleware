<?php

namespace Webong\GuzzleTelescopeMiddleware;

class GuzzleHandled
{
    /**
     * The request instance.
     *
     * @var \GuzzleHttp\Psr7\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \GuzzleHttp\Psr7\Response
     */
    public $response;

    public $options;

    public $error;

    /**
     * Create a new event instance.
     *
     * @param  \GuzzleHttp\Psr7\Request  $request
     * @param  \GuzzleHttp\Psr7\Response  $response
     */
    public function __construct($request, $response, $options, $error = null)
    {
        $this->request = $request;
        $this->response = $response;
        $this->options = $options;
        $this->error = $error;
    }
}
