<?php

namespace Pterodactyl\Exceptions\Repository;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RecordNotFoundException extends RepositoryException implements HttpExceptionInterface
{
    /**
     * Returns the status code.
     */
    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    /**
     * Returns response headers.
     */
    public function getHeaders(): array
    {
        return [];
    }
}
