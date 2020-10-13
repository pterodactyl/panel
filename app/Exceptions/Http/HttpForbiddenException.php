<?php

namespace Pterodactyl\Exceptions\Http;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpForbiddenException extends HttpException
{
    /**
     * HttpForbiddenException constructor.
     *
     * @param string|null $message
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = null, \Throwable $previous = null)
    {
        parent::__construct(Response::HTTP_FORBIDDEN, $message, $previous);
    }
}
