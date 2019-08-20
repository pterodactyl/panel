<?php

namespace App\Exceptions\Service;

use Illuminate\Http\Response;
use App\Exceptions\DisplayException;

class HasActiveServersException extends DisplayException
{
    /**
     * @return int
     */
    public function getStatusCode()
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
