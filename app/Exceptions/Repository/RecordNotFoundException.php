<?php

namespace Pterodactyl\Exceptions\Repository;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RecordNotFoundException extends RepositoryException implements HttpExceptionInterface
{
    /**
     * Returns the status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return Response::HTTP_NOT_FOUND;
    }

    /**
     * Returns response headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return [];
    }
}
