<?php

namespace Pterodactyl\Exceptions\Http;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QueryValueOutOfRangeHttpException extends HttpException
{
    /**
     * QueryValueOutOfRangeHttpException constructor.
     */
    public function __construct(string $name, int $min, int $max, \Throwable $previous = null)
    {
        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            '\"' . $name . '\" query parameter must be between ' . $min . ' and ' . $max,
            $previous,
        );
    }
}
