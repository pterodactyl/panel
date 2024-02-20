<?php

namespace Pterodactyl\Exceptions\Http;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TwoFactorAuthRequiredException extends HttpException implements HttpExceptionInterface
{
    /**
     * TwoFactorAuthRequiredException constructor.
     */
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct(Response::HTTP_BAD_REQUEST, 'Two-factor authentication is required on this account in order to access this endpoint.', $previous);
    }
}
