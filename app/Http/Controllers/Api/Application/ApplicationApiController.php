<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application;

use Illuminate\Http\Response;
use Pterodactyl\Http\Controllers\Controller;

abstract class ApplicationApiController extends Controller
{
    /**
     * Return an HTTP/204 response for the API.
     */
    protected function returnNoContent(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
