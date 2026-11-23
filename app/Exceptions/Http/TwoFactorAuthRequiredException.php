<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Http;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class TwoFactorAuthRequiredException extends HttpException implements HttpExceptionInterface
{
    /**
     * TwoFactorAuthRequiredException constructor.
     */
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(Response::HTTP_BAD_REQUEST, __('exceptions.account.two_factor_required'), $previous);
    }
}
