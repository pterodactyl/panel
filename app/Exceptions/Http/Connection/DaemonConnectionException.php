<?php

namespace Pterodactyl\Exceptions\Http\Connection;

use Illuminate\Http\Response;
use GuzzleHttp\Exception\GuzzleException;
use Pterodactyl\Exceptions\DisplayException;

class DaemonConnectionException extends DisplayException
{
    /**
     * @var int
     */
    private $statusCode = Response::HTTP_GATEWAY_TIMEOUT;

    /**
     * Throw a displayable exception caused by a daemon connection error.
     *
     * @param \GuzzleHttp\Exception\GuzzleException $previous
     * @param bool                                  $useStatusCode
     */
    public function __construct(GuzzleException $previous, bool $useStatusCode = false)
    {
        /** @var \GuzzleHttp\Psr7\Response|null $response */
        $response = method_exists($previous, 'getResponse') ? $previous->getResponse() : null;

        if ($useStatusCode) {
            $this->statusCode = is_null($response) ? 500 : $response->getStatusCode();
        }

        parent::__construct(trans('admin/server.exceptions.daemon_exception', [
            'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
        ]), $previous, DisplayException::LEVEL_WARNING);
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
