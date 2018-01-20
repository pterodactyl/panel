<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use ReflectionMethod;
use Illuminate\Container\Container;
use Illuminate\Routing\ImplicitRouteBinding;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Routing\Middleware\SubstituteBindings;

class ApiSubstituteBindings extends SubstituteBindings
{
    /**
     * Perform substitution of route parameters without triggering
     * a 404 error if a model is not found.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();

        $this->router->substituteBindings($route);
        $this->resolveForRoute($route);

        return $next($request);
    }

    /**
     * Resolve the implicit route bindings for the given route. This function
     * overrides Laravel's default inn \Illuminate\Routing\ImplictRouteBinding
     * to not throw a 404 error when a model is not found.
     *
     * If a model is not found using the provided information, the binding is
     * replaced with null which is then checked in the form requests on API
     * routes. This avoids a potential imformation leak on API routes for
     * unauthenticated users.
     *
     * @param \Illuminate\Routing\Route $route
     */
    protected function resolveForRoute($route)
    {
        $parameters = $route->parameters();

        // Avoid having to copy and paste the entirety of that class into this middleware
        // by using reflection to access a protected method.
        $reflection = new ReflectionMethod(ImplicitRouteBinding::class, 'getParameterName');
        $reflection->setAccessible(true);

        foreach ($route->signatureParameters(UrlRoutable::class) as $parameter) {
            if (! $parameterName = $reflection->invokeArgs(null, [$parameter->name, $parameters])) {
                continue;
            }

            $parameterValue = $parameters[$parameterName];

            if ($parameterValue instanceof UrlRoutable) {
                continue;
            }

            // Try to find an existing model, if one is not found simply bind the
            // parameter as null.
            $instance = Container::getInstance()->make($parameter->getClass()->name);
            $route->setParameter($parameterName, $instance->resolveRouteBinding($parameterValue));
        }
    }
}
