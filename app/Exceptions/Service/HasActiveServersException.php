<?php

namespace Pterodactyl\Exceptions\Service;

use Illuminate\Http\Response;
use Pterodactyl\Exceptions\DisplayException;

class HasActiveServersException extends DisplayException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
