<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal;

abstract class ApplicationApiController extends Controller
{
    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @var \Spatie\Fractalistic\Fractal
     */
    protected $fractal;

    /**
     * ApplicationApiController constructor.
     *
     * @param \Pterodactyl\Extensions\Spatie\Fractalistic\Fractal $fractal
     * @param \Illuminate\Http\Request                            $request
     */
    public function __construct(Fractal $fractal, Request $request)
    {
        $this->fractal = $fractal;
        $this->request = $request;

        // Parse all of the includes to use on this request.
        $includes = collect(explode(',', $request->input('include', '')))->map(function ($value) {
            return trim($value);
        })->filter()->toArray();

        $this->fractal->parseIncludes($includes);
        $this->fractal->limitRecursion(2);
    }

    /**
     * Return an instance of an application transformer.
     *
     * @param string $abstract
     * @return \Pterodactyl\Transformers\Api\Application\BaseTransformer
     */
    public function getTransformer(string $abstract)
    {
        /** @var \Pterodactyl\Transformers\Api\Application\BaseTransformer $transformer */
        $transformer = Container::getInstance()->make($abstract);
        $transformer->setKey($this->request->attributes->get('api_key'));

        return $transformer;
    }
}
