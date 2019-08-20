<?php

namespace App\Exceptions\Service\Location;

use Illuminate\Http\Response;
use App\Exceptions\DisplayException;

class HasActiveNodesException extends DisplayException
{
    /**
     * @return int
     */
    public function getStatusCode()
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
