<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Illuminate\Http\Request;
use Webmozart\Assert\Assert;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;

abstract class ApplicationApiController extends Controller
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Pterodactyl\Extensions\Spatie\Fractalistic\Fractal
     */
    protected $fractal;

    /**
     * ApplicationApiController constructor.
     */
    public function __construct()
    {
        Container::getInstance()->call([$this, 'loadDependencies']);

        // Parse all of the includes to use on this request.
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
     * Return an instance of an application transformer.
     *
     * @return \Pterodactyl\Transformers\Api\Application\BaseTransformer
     */
    public function getTransformer(string $abstract)
    {
        /** @var \Pterodactyl\Transformers\Api\Application\BaseTransformer $transformer */
        $transformer = Container::getInstance()->make($abstract);
        $transformer->setKey($this->request->attributes->get('api_key'));

        Assert::isInstanceOf($transformer, BaseTransformer::class);

        return $transformer;
    }

    /**
     * Return a HTTP/204 response for the API.
     */
    protected function returnNoContent(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
