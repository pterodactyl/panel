<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Extensions\ExtensionProviderTest;

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Mockery;
use Pterodactyl\Extensions\ExtensionProvider;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Api\Application\AuthorizeExtensionApplicationRequest;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerParameterAccess;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Tests\TestCase;
use RuntimeException;

use function pterodactylTestCase;

uses(TestCase::class);
beforeEach(function () {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-extension-provider-'.uniqid();
    $this->bootstrapPath = $this->app->bootstrapPath();
    File::ensureDirectoryExists($this->directory);
});
afterEach(function () {
    $this->app->useBootstrapPath($this->bootstrapPath);
    File::deleteDirectory($this->directory);
});
test('client api routes load through laravel route group', function () {
    $provider = provider('route-fixture');
    $path = writeRouteFile('client.php', '/status', 'status');
    useTemporaryBootstrapPath('bootstrap-uncached');
    $provider->clientApiRoutes($path);
    $route = routeByUri('api/client/extensions/route-fixture/status');
    expect($route)->not->toBeNull();
    expect($route->getName())->toBe('extensions.route-fixture.client.status');
    expect($route->uri())->toBe('api/client/extensions/route-fixture/status');
    expect($route->middleware())->toContain('api');
    expect($route->middleware())->toContain('client-api');
    expect($route->middleware())->toContain('throttle:api.client');
    expect($route->middleware())->toContain(RequireTwoFactorAuthentication::class);
    expect($route->middleware())->toContain(AuthenticateServerParameterAccess::class);
    expect($route->enforcesScopedBindings())->toBeTrue();
});
test('application api routes load with the api key scope check', function () {
    $provider = provider('application-fixture');
    $path = writeRouteFile('application.php', '/report', 'report');
    useTemporaryBootstrapPath('bootstrap-application');
    $provider->applicationApiRoutes($path);
    $route = routeByUri('api/application/extensions/application-fixture/report');
    expect($route)->not->toBeNull();
    expect($route->getName())->toBe('extensions.application-fixture.application.report');
    expect($route->middleware())->toContain('application-api');
    expect($route->middleware())->toContain('throttle:api.application');
    expect($route->middleware())->toContain(AuthorizeExtensionApplicationRequest::class);
    expect($route->enforcesScopedBindings())->toBeTrue();
});
test('admin api routes load through laravel route group', function () {
    $provider = provider('admin-fixture');
    $path = writeRouteFile('admin.php', '/settings', 'settings');
    useTemporaryBootstrapPath('bootstrap-admin');
    $provider->adminApiRoutes($path);
    $route = routeByUri('api/admin/extensions/admin-fixture/settings');
    expect($route)->not->toBeNull();
    expect($route->getName())->toBe('extensions.admin-fixture.admin.settings');
    expect($route->uri())->toBe('api/admin/extensions/admin-fixture/settings');
    expect($route->middleware())->toContain('api');
    expect($route->middleware())->toContain('admin-api');
    expect($route->middleware())->toContain('throttle:api.admin');
    expect($route->middleware())->toContain(RequireTwoFactorAuthentication::class);
    expect($route->enforcesScopedBindings())->toBeTrue();
});
test('server api routes load with the server scoped client stack', function () {
    $provider = provider('server-fixture');
    $path = writeRouteFile('server.php', '/mods', 'mods');
    useTemporaryBootstrapPath('bootstrap-server');
    $provider->serverApiRoutes($path);
    $route = routeByUri('api/client/servers/{server}/extensions/server-fixture/mods');
    expect($route)->not->toBeNull();
    expect($route->getName())->toBe('extensions.server-fixture.server.mods');
    expect($route->middleware())->toContain('api');
    expect($route->middleware())->toContain('client-api');
    expect($route->middleware())->toContain('throttle:api.client');
    expect($route->middleware())->toContain(RequireTwoFactorAuthentication::class);
    expect($route->middleware())->toContain(ServerSubject::class);
    expect($route->middleware())->toContain(AuthenticateServerAccess::class);
    expect($route->middleware())->toContain(ResourceBelongsToServer::class);
    expect($route->enforcesScopedBindings())->toBeTrue();
});
test('conventional api routes load when route files exist', function () {
    $provider = provider('convention-fixture');
    writeExtensionRouteFile('convention-fixture', 'client.php', '/client-status', 'client-status');
    writeExtensionRouteFile('convention-fixture', 'admin.php', '/admin-settings', 'admin-settings');
    useTemporaryBootstrapPath('bootstrap-convention');
    $provider->apiRoutes();
    $client = routeByUri('api/client/extensions/convention-fixture/client-status');
    $admin = routeByUri('api/admin/extensions/convention-fixture/admin-settings');
    expect($client)->not->toBeNull();
    expect($client->getName())->toBe('extensions.convention-fixture.client.client-status');
    expect($client->middleware())->toContain('client-api');
    expect($client->middleware())->toContain('throttle:api.client');
    expect($admin)->not->toBeNull();
    expect($admin->getName())->toBe('extensions.convention-fixture.admin.admin-settings');
    expect($admin->middleware())->toContain('admin-api');
    expect($admin->middleware())->toContain('throttle:api.admin');
});
test('route files are not loaded when laravel routes are cached', function () {
    $provider = provider('cached-fixture');
    $path = writeRouteFile('cached.php', '/cached', 'cached');
    useTemporaryBootstrapPath('bootstrap-cached');
    File::put($this->app->getCachedRoutesPath(), '<?php return [];');
    // The framework memoizes the cache check at boot, before the file above exists.
    $this->app->forgetInstance('routes.cached');
    $provider->clientApiRoutes($path);
    expect(routeByUri('api/client/extensions/cached-fixture/cached'))->toBeNull();
});
function routeByUri(string $uri): ?IlluminateRoute
{
    foreach (Route::getRoutes() as $route) {
        if ($route->uri() === $uri) {
            return $route;
        }
    }

    return null;
}
function useTemporaryBootstrapPath(string $directory): void
{
    (function () use ($directory) {
        $bootstrap = $this->directory.DIRECTORY_SEPARATOR.$directory;
        File::ensureDirectoryExists($bootstrap.DIRECTORY_SEPARATOR.'cache');
        $this->app->useBootstrapPath($bootstrap);
    })->call(pterodactylTestCase());
}
function provider(string $identifier): ExtensionProvider
{
    return (function () use ($identifier) {
        $path = $this->directory.DIRECTORY_SEPARATOR.$identifier;
        File::ensureDirectoryExists($path);
        File::put($path.DIRECTORY_SEPARATOR.ExtensionManifest::FILENAME, json_encode(['id' => $identifier, 'name' => 'Route Fixture', 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
        $manifest = (new ExtensionManifestValidator)->fromDirectory($path);

        return new class($this->app, $manifest) extends ExtensionProvider
        {
            public function clientApiRoutes(string $path): void
            {
                $this->registerClientApiRoutes($path);
            }

            public function adminApiRoutes(string $path): void
            {
                $this->registerAdminApiRoutes($path);
            }

            public function applicationApiRoutes(string $path): void
            {
                $this->registerApplicationApiRoutes($path);
            }

            public function serverApiRoutes(string $path): void
            {
                $this->registerServerApiRoutes($path);
            }

            public function operationListener(callable $listener): void
            {
                $this->listenToServerOperations($listener);
            }

            public function apiRoutes(?string $directory = null): void
            {
                $this->registerApiRoutes($directory);
            }
        };
    })->call(pterodactylTestCase());
}
function writeRouteFile(string $filename, string $uri, string $name): string
{
    return (function () use ($filename, $uri, $name) {
        $path = $this->directory.DIRECTORY_SEPARATOR.$filename;
        File::put($path, <<<PHP
        <?php

        use Illuminate\\Support\\Facades\\Route;

        Route::get('{$uri}', fn () => 'ok')->name('{$name}');
        PHP);

        return $path;
    })->call(pterodactylTestCase());
}
function writeExtensionRouteFile(string $identifier, string $filename, string $uri, string $name): string
{
    return (function () use ($identifier, $filename, $uri, $name) {
        $directory = $this->directory.DIRECTORY_SEPARATOR.$identifier.DIRECTORY_SEPARATOR.'routes';
        File::ensureDirectoryExists($directory);

        return writeRouteFileTo($directory.DIRECTORY_SEPARATOR.$filename, $uri, $name);
    })->call(pterodactylTestCase());
}
function writeRouteFileTo(string $path, string $uri, string $name): string
{
    File::put($path, <<<PHP
    <?php

    use Illuminate\\Support\\Facades\\Route;

    Route::get('{$uri}', fn () => 'ok')->name('{$name}');
    PHP);

    return $path;
}

test('curated operation listeners isolate extension failures from other listeners', function (): void {
    $repository = Mockery::mock(\Pterodactyl\Services\Extensions\ExtensionRepository::class);
    $repository->shouldReceive('recordFailure')->once()->with('operation-probe', 'failed', Mockery::type(RuntimeException::class), 'event');
    $this->app->instance(\Pterodactyl\Services\Extensions\ExtensionRepository::class, $repository);
    provider('operation-probe')->operationListener(function (): void {
        throw new RuntimeException('failed');
    });
    $seen = [];
    \Illuminate\Support\Facades\Event::listen(\Pterodactyl\Events\Server\OperationCompleted::class,
        function ($event) use (&$seen): void {
            $seen[] = $event->resourceUuid;
        });
    \Illuminate\Support\Facades\Event::dispatch(new \Pterodactyl\Events\Server\OperationCompleted('server', 'backup', true, 'backup'));
    expect($seen)->toBe(['backup']);
});
