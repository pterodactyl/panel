<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Exception;
use Illuminate\Support\Reflector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Routing\UrlRoutable;

class PreventUnboundModels
{
    /**
     * Prevents a request from making it to a controller action if there is a model
     * injection on the controller that has not been explicitly bound by the request.
     * This prevents empty models from being valid in scenarios where a new model is
     * added but not properly defined in the substitution middleware.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();
        $parameters = $route->parameters() ?? [];

        /** @var \ReflectionParameter[] $signatures */
        $signatures = $route->signatureParameters(UrlRoutable::class);
        foreach ($signatures as $signature) {
            $class = Reflector::getParameterClassName($signature);
            if (is_null($class) || !is_subclass_of($class, Model::class)) {
                continue;
            }

            if (!$parameters[$signature->getName()] instanceof Model) {
                throw new Exception(sprintf('No parameter binding has been defined for model [%s] using route parameter key "%s".', $class, $signature->getName()));
            }
        }

        return $next($request);
    }
}
