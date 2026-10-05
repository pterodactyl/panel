<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Http;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class HttpForbiddenException extends HttpException
{
    /**
     * HttpForbiddenException constructor.
     */
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct(Response::HTTP_FORBIDDEN, $message, $previous);
    }
}
