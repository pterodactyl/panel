<?php

namespace Pterodactyl\Exceptions\Http\Connection;

use GuzzleHttp\Exception\GuzzleException;
use Pterodactyl\Exceptions\DisplayException;

class DaemonConnectionException extends DisplayException
{
    /**
     * Throw a displayable exception caused by a daemon connection error.
     *
     * @param \GuzzleHttp\Exception\GuzzleException $previous
     */
    public function __construct(GuzzleException $previous)
    {
        /** @var \GuzzleHttp\Psr7\Response|null $response */
        $response = method_exists($previous, 'getResponse') ? $previous->getResponse() : null;

        parent::__construct(trans('admin/server.exceptions.daemon_exception', [
            'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
        ]), $previous, DisplayException::LEVEL_WARNING);
    }
}
