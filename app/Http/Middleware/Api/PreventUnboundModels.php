<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Illuminate\Support\Reflector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Container\BindingResolutionException;

class PreventUnboundModels
{
    /**
     * Prevents a request from making it to a controller action if there is a model
     * injection on the controller that has not been explicitly bound by the request.
     * This prevents empty models from being valid in scenarios where a new model is
     * added but not properly defined in the substitution middleware.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();

        $parameters = $route->signatureParameters(UrlRoutable::class);
        for ($i = 0; $i < count($route->parameters()); $i++) {
            $class = Reflector::getParameterClassName($parameters[$i + 1]);

            // Skip anything that isn't explicitly requested as a model.
            if (is_null($class) || !is_subclass_of($class, Model::class)) {
                continue;
            }

            if (!array_values($route->parameters())[$i] instanceof $class) {
                throw new BindingResolutionException(
                    sprintf(
                        'No parameter binding has been defined for model [%s] using route parameter key "%s".',
                        $class,
                        array_keys($route->parameters())[$i]
                    )
                );
            }
        }

        return $next($request);
    }
}
