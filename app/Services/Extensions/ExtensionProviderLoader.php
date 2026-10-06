<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Composer\Autoload\ClassLoader;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Extensions\ExtensionProvider;
use Throwable;

class ExtensionProviderLoader
{
    private ?ClassLoader $classLoader = null;

    /** @var array<string, true> */
    private array $autoloadedPaths = [];

    /** @var array<string, ExtensionProvider> */
    private array $providers = [];

    public function __construct(
        private readonly Application $app,
        private readonly ExtensionRepository $extensions,
    ) {}

    /**
     * @param  Collection<string, ExtensionManifest>  $enabled
     */
    public function registerProviders(Collection $enabled): void
    {
        $this->registerAutoloader($enabled);

        $router = $this->app->make(Router::class);
        $registered = [];
        foreach ($enabled as $manifest) {
            if (isset($this->providers[$manifest->id])) {
                continue;
            }

            $provider = null;
            // Cached routes are compiled and are not copied, so nothing is taken back then.
            $routes = $router->getRoutes();
            $routes = $routes instanceof RouteCollection ? clone $routes : null;
            try {
                foreach (array_keys($manifest->requiredExtensions) as $dependency) {
                    throw_unless(in_array($dependency, $registered, true) || isset($this->providers[$dependency]), InvalidExtensionException::class, "Required extension \"{$dependency}\" failed to load.");
                }

                $this->registerComposerAutoloader($manifest);
                if ($manifest->provider === null) {
                    $registered[] = $manifest->id;

                    continue;
                }

                $provider = $this->makeProvider($manifest);
                $provider->beginRegistration();
                $this->app->register($provider, force: true);
                $provider->commitRegistration();
                $this->providers[$manifest->id] = $provider;
                $registered[] = $manifest->id;
            } catch (Throwable $exception) {
                $provider?->discardRegistration();
                // Routes the provider added with the Route facade instead of the staged
                // helpers would otherwise stay live; see ExtensionProvider for what remains.
                if ($routes instanceof RouteCollection) {
                    $router->setRoutes($routes);
                }

                $this->extensions->recordFailure(
                    $manifest->id,
                    $exception->getMessage(),
                    $exception,
                    $this->registeredProvider($provider) ? 'boot' : 'register',
                );
            }
        }

        $this->extensions->clearErrors($registered);
    }

    public function bootProviders(): void
    {
        // Providers are booted by Application::register() because the extension
        // loader runs from an app-booted callback.
    }

    /**
     * @param  Collection<string, ExtensionManifest>  $manifests
     */
    public function registerAutoloader(Collection $manifests): void
    {
        foreach ($manifests as $manifest) {
            foreach ($manifest->autoload as $prefix => $src) {
                $path = $manifest->path($src);
                $key = $prefix.'|'.$path;

                if (isset($this->autoloadedPaths[$key])) {
                    continue;
                }

                $this->loader()->addPsr4($prefix, $path);
                $this->autoloadedPaths[$key] = true;
            }
        }
    }

    private function registerComposerAutoloader(ExtensionManifest $manifest): void
    {
        $path = $manifest->path('vendor', 'autoload.php');
        if (! is_file($path) || isset($this->autoloadedPaths[$path])) {
            return;
        }

        $loader = require $path;
        throw_unless($loader instanceof ClassLoader, InvalidExtensionException::class, 'Extension vendor/autoload.php must return a Composer ClassLoader.');
        // Composer registers its loader in front of every other one. Behind the panel's, a
        // package the extension bundles can never replace the copy the panel loads.
        $loader->unregister();
        $loader->register(prepend: false);
        $this->autoloadedPaths[$path] = true;
    }

    private function makeProvider(ExtensionManifest $manifest): ExtensionProvider
    {
        if ($manifest->provider === null || ! class_exists($manifest->provider)) {
            throw new InvalidExtensionException("Provider class {$manifest->provider} was not found via the manifest autoload map.");
        }

        if (! is_subclass_of($manifest->provider, ExtensionProvider::class)) {
            throw new InvalidExtensionException("{$manifest->provider} must extend ".ExtensionProvider::class.'.');
        }

        return new ($manifest->provider)($this->app, $manifest);
    }

    private function loader(): ClassLoader
    {
        if (! $this->classLoader instanceof ClassLoader) {
            // Appended behind the panel's own loader, so the manifest's PSR-4 map only
            // serves classes the panel and its packages do not provide.
            $this->classLoader = new ClassLoader;
            $this->classLoader->register(prepend: false);
        }

        return $this->classLoader;
    }

    private function registeredProvider(?ExtensionProvider $provider): bool
    {
        return $provider instanceof ExtensionProvider && $this->app->getProvider($provider::class) === $provider;
    }
}
