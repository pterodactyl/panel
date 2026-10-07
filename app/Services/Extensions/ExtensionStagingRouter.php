<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;

/**
 * The router extension route files register against while a provider's registration
 * is staged. It starts as a copy of the panel's router, and the routes, binders
 * (`Route::bind`, `Route::model`) and patterns (`Route::pattern`) the files add only
 * reach the panel's router through apply(), so a failed commit leaves none behind.
 */
final class ExtensionStagingRouter extends Router
{
    public function __construct(private readonly Router $router)
    {
        parent::__construct($router->events, $router->container);

        $this->middleware = $router->middleware;
        $this->middlewareGroups = $router->middlewareGroups;
        $this->middlewarePriority = $router->middlewarePriority;
        $this->binders = $router->binders;
        $this->patterns = $router->patterns;
        $this->groupStack = $router->groupStack;
        $this->implicitBindingCallback = $router->implicitBindingCallback;

        $routes = $router->getRoutes();
        if ($routes instanceof RouteCollection) {
            $this->setRoutes(clone $routes);
        } else {
            $this->routes = $routes;
        }
    }

    public function apply(): void
    {
        $routes = $this->getRoutes();
        if ($routes instanceof RouteCollection) {
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
            $this->router->setRoutes($routes);
        }

        $this->router->binders = [...$this->router->binders, ...$this->binders];
        $this->router->patterns = [...$this->router->patterns, ...$this->patterns];
    }
}
