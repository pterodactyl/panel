<?php

namespace Pterodactyl\Exceptions\Http\Connection;

use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use GuzzleHttp\Exception\GuzzleException;
use Pterodactyl\Exceptions\DisplayException;

/**
 * @method \GuzzleHttp\Exception\GuzzleException getPrevious()
 */
class DaemonConnectionException extends DisplayException
{
    /**
     * @var int
     */
    private $statusCode = Response::HTTP_GATEWAY_TIMEOUT;

    /**
     * Throw a displayable exception caused by a daemon connection error.
     */
    public function __construct(GuzzleException $previous, bool $useStatusCode = true)
    {
        /** @var \GuzzleHttp\Psr7\Response|null $response */
        $response = method_exists($previous, 'getResponse') ? $previous->getResponse() : null;

        if ($useStatusCode) {
            $this->statusCode = is_null($response) ? $this->statusCode : $response->getStatusCode();
        }

        $message = trans('admin/server.exceptions.daemon_exception', [
            'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
        ]);

        // Attempt to pull the actual error message off the response and return that if it is not
        // a 500 level error.
        if ($this->statusCode < 500 && !is_null($response)) {
            $body = $response->getBody();
            if (is_string($body) || (is_object($body) && method_exists($body, '__toString'))) {
                $body = json_decode(is_string($body) ? $body : $body->__toString(), true);
                $message = '[Wings Error]: ' . Arr::get($body, 'error', $message);
            }
        }

        $level = $this->statusCode >= 500 && $this->statusCode !== 504
            ? DisplayException::LEVEL_ERROR
            : DisplayException::LEVEL_WARNING;

        parent::__construct($message, $previous, $level);
    }

    /**
     * Return the HTTP status code for this exception.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
