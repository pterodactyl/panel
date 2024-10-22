<?php

namespace Pterodactyl\Exceptions\Http;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpForbiddenException extends HttpException
{
    /**
     * HttpForbiddenException constructor.
     */
    public function __construct(?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(Response::HTTP_FORBIDDEN, $message, $previous);
    }
}
