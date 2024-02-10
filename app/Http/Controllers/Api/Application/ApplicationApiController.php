<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal;

abstract class ApplicationApiController extends Controller
{
    protected Fractal $fractal;
    protected Request $request;

    /**
     * ApplicationApiController constructor.
     */
    public function __construct()
    {
        Container::getInstance()->call([$this, 'loadDependencies']);

        // Parse all the includes to use on this request.
        $input = $this->request->input('include', []);
        $input = is_array($input) ? $input : explode(',', $input);

        $includes = (new Collection($input))->map(function ($value) {
            return trim($value);
        })->filter()->toArray();

        $this->fractal->parseIncludes($includes);
        $this->fractal->limitRecursion(2);
    }

    /**
     * Perform dependency injection of certain classes needed for core functionality
     * without littering the constructors of classes that extend this abstract.
     */
    public function loadDependencies(Fractal $fractal, Request $request)
    {
        $this->fractal = $fractal;
        $this->request = $request;
    }

    /**
     * Return an HTTP/201 response for the API.
     */
    protected function returnAccepted(): Response
    {
        return new Response('', Response::HTTP_ACCEPTED);
    }

    /**
     * Return an HTTP/204 response for the API.
     */
    protected function returnNoContent(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
