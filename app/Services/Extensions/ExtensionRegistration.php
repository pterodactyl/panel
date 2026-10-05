<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Throwable;

/** Stages the provider helpers until Laravel has successfully booted the provider. */
final class ExtensionRegistration
{
    /** @var list<Closure(): void>|null */
    private ?array $pending = null;

    private bool $active = true;

    public function __construct(
        private readonly Application $app,
        private readonly Router $router,
        private readonly ExtensionSettingsRegistry $settings,
        private readonly ExtensionPermissionRegistry $permissions,
        private readonly ExtensionActionDecorators $actions,
        private readonly ExtensionConsoleRegistry $console,
        private readonly ExtensionHeadTags $headTags,
    ) {}

    public function begin(): void
    {
        $this->pending = [];
        $this->active = false;
    }

    /** @param Closure(): void $registration */
    public function defer(Closure $registration): void
    {
        if ($this->pending === null) {
            $registration();

            return;
        }

        $this->pending[] = $registration;
    }

    public function commit(string $identifier): void
    {
        if ($this->pending === null) {
            return;
        }

        $definition = $this->settings->get($identifier);
        $permissions = $this->permissions->all()[ExtensionPermissionRegistry::group($identifier)] ?? null;
        $actions = $this->actions->snapshot();
        $console = $this->console->snapshot();
        $headTags = $this->headTags->snapshot();
        $router = clone $this->router;
        $routes = $this->router->getRoutes();
        if ($routes instanceof RouteCollection) {
            $router->setRoutes(clone $routes);
        }

        Route::swap($router);
        $registrations = $this->pending;
        $this->pending = null;
        try {
            foreach ($registrations as $registration) {
                $registration();
            }

            $routes = $router->getRoutes();
            if ($routes instanceof RouteCollection) {
                $routes->refreshNameLookups();
                $routes->refreshActionLookups();
                $this->router->setRoutes($routes);
            }

            $this->active = true;
        } catch (Throwable $throwable) {
            $this->settings->unregister($identifier);
            if ($definition instanceof ExtensionSettingsDefinition) {
                $this->settings->register($identifier, $definition);
            }

            $this->permissions->unregister($identifier);
            if ($permissions !== null) {
                $this->permissions->register($identifier, $permissions['description'], $permissions['keys']);
            }

            $this->actions->restore($actions);
            $this->console->restore($console);
            $this->headTags->restore($headTags);

            throw $throwable;
        } finally {
            Route::swap($this->router);
            $routes = $this->router->getRoutes();
            if ($routes instanceof RouteCollection) {
                $this->router->setRoutes($routes);
            }

            $this->app->instance('routes', $routes);
            $this->pending = null;
        }
    }

    public function discard(): void
    {
        $this->pending = null;
        $this->active = false;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
