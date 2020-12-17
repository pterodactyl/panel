<?php

namespace Pterodactyl\Exceptions\Http\Server;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServerTransferringException extends HttpException
{
    public function __construct()
    {
        parent::__construct(Response::HTTP_CONFLICT, 'Server is currently being transferred.');
    }
}
