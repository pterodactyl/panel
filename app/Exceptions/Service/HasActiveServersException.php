<?php

namespace Pterodactyl\Exceptions\Service;

use Illuminate\Http\Response;
use Pterodactyl\Exceptions\DisplayException;

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
