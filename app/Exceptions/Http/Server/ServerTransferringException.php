<?php

namespace Pterodactyl\Exceptions\Http\Server;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServerTransferringException extends HttpException
{
    /**
     * ServerTransferringException constructor.
     */
    public function __construct()
    {
        parent::__construct(Response::HTTP_CONFLICT, 'This server is currently being transferred to a new machine, please try again laster.');
    }
}
