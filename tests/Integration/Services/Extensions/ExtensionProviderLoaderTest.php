<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Extensions\ExtensionProviderLoaderTest;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Pterodactyl\Actions\Servers\DeleteServer;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Events\Extensions\ExtensionLoadFailed;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Extensions\ExtensionProvider;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionConsoleRegistry;
use Pterodactyl\Services\Extensions\ExtensionFormFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionHeadTags;
use Pterodactyl\Services\Extensions\ExtensionManager;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;
use Pterodactyl\Services\Extensions\ExtensionProviderLoader;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingsRegistry;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->extensionDirectory = sys_get_temp_dir().'/ptero-registration-'.uniqid();
    File::ensureDirectoryExists($this->extensionDirectory);
    config([
        'extensions.enabled' => true,
        'extensions.directory' => $this->extensionDirectory,
        'extensions.assets_directory' => $this->extensionDirectory.'/assets',
        'extension-loader.failures' => [],
    ]);
    $this->app->forgetInstance(ExtensionRepository::class);
    $this->app->forgetInstance(ExtensionProviderLoader::class);
    $this->app->forgetInstance(ExtensionManager::class);
});

afterEach(function (): void {
    File::deleteDirectory($this->extensionDirectory);
});

test('failed providers and their dependents expose no registrations while healthy extensions remain available', function (string $phase): void {
    config(['extension-loader.failures.a-failed' => $phase]);
    writeExtension('a-failed', routeFailure: $phase === 'route');
    writeExtension('b-dependent', ['a-failed' => '^1.0']);
    writeExtension('c-transitive', ['b-dependent' => '^1.0']);
    writeExtension('z-healthy');
    Event::fake([ExtensionLoadFailed::class, RegistrationOperationObserved::class]);
    $repository = $this->app->make(ExtensionRepository::class);

    $this->app->make(ExtensionProviderLoader::class)->registerProviders($repository->enabled());
    Event::dispatch(new OperationCompleted('server', 'install', true, 'completed'));

    expect(array_column($repository->frontendPayload(false), 'id'))->toBe(['z-healthy']);
    $repository->flushDiscovery();
    expect($repository->enabled()->keys()->all())->toBe(['z-healthy']);
    foreach (['a-failed', 'b-dependent', 'c-transitive'] as $identifier) {
        expect(Route::has('extensions.'.$identifier.'.client.status'))->toBeFalse();
        expect($this->app->make(ExtensionSettingsRegistry::class)->has($identifier))->toBeFalse();
        expect($this->app->make(ExtensionPermissionRegistry::class)->all())->not->toHaveKey('ext.'.$identifier);
        expect($this->app->make(ExtensionFormFieldRegistry::class)->forms($identifier))->toBe([]);
        $this->assertDatabaseHas('extensions', ['identifier' => $identifier, 'enabled' => true]);
    }

    expect($this->app->make(ExtensionSettingsRegistry::class)->has('z-healthy'))->toBeTrue();
    expect($this->app->make(ExtensionPermissionRegistry::class)->all()['ext.z-healthy']['keys'])->toBe(['view' => 'View extension data.']);
    expect($this->app->make(ExtensionFormFieldRegistry::class)->forms('z-healthy'))->toBe(['admin.user']);

    $console = $this->app->make(ExtensionConsoleRegistry::class)->snapshot();
    expect($console['commands'])->toBe(['z-healthy' => [RegistrationProbeCommand::class]]);
    expect(array_keys($console['schedules']))->toBe(['z-healthy']);
    expect($this->app->make(ExtensionHeadTags::class)->toHtml())->toBe('<meta name="registration-probe-z-healthy" content="ready">');

    $action = $this->app->make(DeletesServers::class);
    expect($action)->toBeInstanceOf(RegistrationProbeDeleter::class);
    expect($action->owner)->toBe('z-healthy');
    expect($action->inner)->toBeInstanceOf(DeleteServer::class);
    Event::assertDispatchedTimes(RegistrationOperationObserved::class, 1);
    Event::assertDispatched(RegistrationOperationObserved::class, fn (RegistrationOperationObserved $event): bool => $event->identifier === 'z-healthy' && $event->resourceUuid === 'completed');
    Event::assertDispatched(ExtensionLoadFailed::class, fn (ExtensionLoadFailed $event): bool => $event->identifier === 'a-failed' && $event->phase === ($phase === 'register' ? 'register' : 'boot'));
    $this->actingAs(User::factory()->create())->getJson('/api/client/extensions/z-healthy/status')->assertOk()->assertContent('ready');
})->with(['register', 'boot', 'route']);

test('failed activation restores existing routes and registry entries', function (): void {
    writeExtension('a-failed', routeFailure: true);
    Event::fake([ExtensionLoadFailed::class]);
    $repository = $this->app->make(ExtensionRepository::class);
    $definition = new ExtensionSettingsDefinition($repository->settings('a-failed'), []);
    $settings = $this->app->make(ExtensionSettingsRegistry::class);
    $permissions = $this->app->make(ExtensionPermissionRegistry::class);
    $settings->register('a-failed', $definition);
    $permissions->register('a-failed', 'Existing permissions.', ['previous' => 'Existing permission.']);
    $formFields = $this->app->make(ExtensionFormFieldRegistry::class);
    $formFields->register('a-failed', 'admin.server', ['previous' => ['string']], null, null, $this->app->make(ExtensionRegistration::class));
    Route::get('/api/registration-preserved', RegistrationProbeController::class)->name('registration-preserved');
    Route::getRoutes()->refreshNameLookups();

    $this->app->make(ExtensionProviderLoader::class)->registerProviders($repository->enabled());

    expect($settings->get('a-failed'))->toBe($definition);
    expect($permissions->all()['ext.a-failed'])->toBe(['description' => 'Existing permissions.', 'keys' => ['previous' => 'Existing permission.']]);
    expect($formFields->forms('a-failed'))->toBe(['admin.server']);
    expect(Route::has('extensions.a-failed.client.status'))->toBeFalse();
    expect($this->app->make(ExtensionConsoleRegistry::class)->snapshot())->toBe(['commands' => [], 'schedules' => []]);
    expect($this->app->make(ExtensionHeadTags::class)->toHtml())->toBe('');
    expect($this->app->make(DeletesServers::class))->toBeInstanceOf(DeleteServer::class);
    $this->getJson('/api/registration-preserved')->assertOk()->assertContent('ready');
});

test('healthy registrations activate after boot and repeated loading does not duplicate listeners', function (): void {
    writeExtension('healthy');
    Event::fake([RegistrationOperationObserved::class]);
    $repository = $this->app->make(ExtensionRepository::class);
    $loader = $this->app->make(ExtensionProviderLoader::class);

    $loader->registerProviders($repository->enabled());
    $loader->registerProviders($repository->enabled());
    Event::dispatch(new OperationCompleted('server', 'backup', true, 'completed'));

    Event::assertDispatchedTimes(RegistrationOperationObserved::class, 1);
    Event::assertDispatched(RegistrationOperationObserved::class, fn (RegistrationOperationObserved $event): bool => $event->identifier === 'healthy' && $event->resourceUuid === 'completed');
    expect(array_column($repository->frontendPayload(false), 'id'))->toBe(['healthy']);
    $this->actingAs(User::factory()->create())->getJson('/api/client/extensions/healthy/status')->assertOk()->assertContent('ready');
});

test('cached extension routes return 404 when their provider fails or extensions are disabled', function (string $state): void {
    $manifest = writeExtension('cached');
    (new RegistrationProbeProvider($this->app, $manifest))->routesForCache();
    $routes = new RouteCollection;
    $route = Route::getRoutes()->getByName('extensions.cached.client.status');
    $routes->add($route);
    Route::setCompiledRoutes($routes->compile());
    Event::fake([ExtensionLoadFailed::class, RegistrationOperationObserved::class]);
    $repository = $this->app->make(ExtensionRepository::class);
    if ($state === 'boot') {
        config(['extension-loader.failures.cached' => 'boot']);
        $this->app->make(ExtensionProviderLoader::class)->registerProviders($repository->enabled());
    } elseif ($state === 'disabled') {
        Extension::query()->where('identifier', 'cached')->update(['enabled' => false]);
        $repository->flushDiscovery();
    } else {
        config(['extensions.enabled' => false]);
    }

    $this->actingAs(User::factory()->create())->getJson('/api/client/extensions/cached/status')->assertNotFound();
})->with(['boot', 'disabled', 'global']);

test('healthy providers remain available when routes are compiled', function (): void {
    $manifest = writeExtension('cached');
    (new RegistrationProbeProvider($this->app, $manifest))->routesForCache();
    $routes = new RouteCollection;
    $routes->add(Route::getRoutes()->getByName('extensions.cached.client.status'));
    Route::setCompiledRoutes($routes->compile());
    $repository = $this->app->make(ExtensionRepository::class);

    $this->app->make(ExtensionProviderLoader::class)->registerProviders($repository->enabled());

    $this->actingAs(User::factory()->create())->getJson('/api/client/extensions/cached/status')->assertOk()->assertContent('ready');
    expect(array_column($repository->frontendPayload(false), 'id'))->toBe(['cached']);
});

test('wrappers, commands and head tags stop applying once their extension is disabled', function (): void {
    writeExtension('healthy');
    $repository = $this->app->make(ExtensionRepository::class);
    $this->app->make(ExtensionProviderLoader::class)->registerProviders($repository->enabled());
    expect($this->app->make(DeletesServers::class))->toBeInstanceOf(RegistrationProbeDeleter::class);
    expect($this->app->make(ExtensionHeadTags::class)->toHtml())->not->toBe('');

    Extension::query()->where('identifier', 'healthy')->update(['enabled' => false]);
    $repository->flushDiscovery();
    $headTags = $this->app->make(ExtensionHeadTags::class);
    $headTags->restore($headTags->snapshot());

    expect($this->app->make(DeletesServers::class))->toBeInstanceOf(DeleteServer::class);
    expect($headTags->toHtml())->toBe('');

    $schedule = new Schedule;
    $this->app->make(ExtensionConsoleRegistry::class)->schedule($schedule);
    expect($schedule->events())->toBe([]);
});

test('operation listener exceptions do not remove successfully loaded extensions', function (): void {
    writeExtension('healthy');
    Event::fake([ExtensionLoadFailed::class]);
    $repository = $this->app->make(ExtensionRepository::class);
    $this->app->make(ExtensionProviderLoader::class)->registerProviders($repository->enabled());
    config(['extension-loader.failures.healthy' => 'event']);

    Event::dispatch(new OperationCompleted('server', 'backup', true, 'completed'));

    Event::assertDispatched(ExtensionLoadFailed::class, fn (ExtensionLoadFailed $event): bool => $event->identifier === 'healthy' && $event->phase === 'event');
    expect(array_column($repository->frontendPayload(false), 'id'))->toBe(['healthy']);
});

test('failure observer exceptions are reported and healthy providers still load', function (): void {
    writeExtension('a-failed');
    writeExtension('z-healthy');
    config(['extension-loader.failures.a-failed' => 'boot']);
    Exceptions::fake();
    Event::listen(ExtensionLoadFailed::class, static function (): void {
        throw new RuntimeException('failure observer failed');
    });
    $repository = $this->app->make(ExtensionRepository::class);

    $this->app->make(ExtensionProviderLoader::class)->registerProviders($repository->enabled());

    expect(array_column($repository->frontendPayload(false), 'id'))->toBe(['z-healthy']);
    $this->assertDatabaseHas('extensions', ['identifier' => 'a-failed', 'error' => 'boot failed']);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'failure observer failed');
    $this->actingAs(User::factory()->create())->getJson('/api/client/extensions/z-healthy/status')->assertOk()->assertContent('ready');
});

/** @param array<string, string> $dependencies */
function writeExtension(string $identifier, array $dependencies = [], bool $routeFailure = false): ExtensionManifest
{
    return (function () use ($identifier, $dependencies, $routeFailure): ExtensionManifest {
        $directory = $this->extensionDirectory.'/'.$identifier;
        File::ensureDirectoryExists($directory.'/routes');
        File::put($directory.'/extension.json', json_encode([
            'id' => $identifier,
            'name' => $identifier,
            'version' => '1.0.0',
            'provider' => RegistrationProbeProvider::class,
            'requires' => ['extensions' => $dependencies],
            'ui' => ['entry' => 'dist/client.js'],
        ], JSON_THROW_ON_ERROR));
        $source = <<<'PHP'
    <?php

    use Illuminate\Support\Facades\Route;
    use Pterodactyl\Tests\Pest\Integration\Services\Extensions\ExtensionProviderLoaderTest\RegistrationProbeController;

    Route::get('/status', RegistrationProbeController::class)->name('status');
    PHP;
        if ($routeFailure) {
            $source .= <<<'PHP'

        Route::prefix('unfinished')->group(function (): void {
            Route::get('/leaked', RegistrationProbeController::class);
            throw new \RuntimeException('route failed');
        });
        PHP;
        }

        File::put($directory.'/routes/client.php', $source);
        Extension::query()->create(['identifier' => $identifier, 'version' => '1.0.0', 'enabled' => true]);

        return $this->app->make(ExtensionManifestValidator::class)->fromDirectory($directory);
    })->call(pterodactylTestCase());
}

final class RegistrationProbeProvider extends ExtensionProvider
{
    public function register(): void
    {
        $this->registerSettings(new ExtensionSettingsDefinition($this->settings(), []));
        $this->registerPermissions('Extension permissions.', ['view' => 'View extension data.']);
        $this->registerFormFields('admin.user', ['plan' => ['nullable', 'string']]);
        $this->wrapAction(DeletesServers::class, fn (DeletesServers $inner): DeletesServers => new RegistrationProbeDeleter($inner, $this->id()));
        $this->registerCommands([RegistrationProbeCommand::class]);
        $this->registerSchedule(function (Schedule $schedule): void {
            $schedule->call(fn (): null => null)->daily()->description('registration probe');
        });
        $this->registerHeadTags([['tag' => 'meta', 'name' => 'registration-probe-'.$this->id(), 'content' => 'ready']]);
        $this->listenToServerOperations(function (OperationCompleted $event): void {
            $this->failAt('event');
            Event::dispatch(new RegistrationOperationObserved($this->id(), $event->resourceUuid));
        });
        $this->failAt('register');
    }

    public function boot(): void
    {
        $this->registerApiRoutes();
        Event::dispatch(new OperationCompleted('server', 'install', true, 'during-boot'));
        $this->failAt('boot');
    }

    public function routesForCache(): void
    {
        $this->registerApiRoutes();
        Route::getRoutes()->refreshNameLookups();
    }

    private function failAt(string $phase): void
    {
        throw_if(config('extension-loader.failures.'.$this->id()) === $phase, RuntimeException::class, $phase.' failed');
    }
}

final class RegistrationProbeController
{
    public function __invoke(): Response
    {
        return new Response('ready');
    }
}

final class RegistrationProbeDeleter implements DeletesServers
{
    public function __construct(public readonly DeletesServers $inner, public readonly string $owner) {}

    public function withForce(bool $bool = true): self
    {
        $this->inner->withForce($bool);

        return $this;
    }

    public function delete(Server $server): void
    {
        $this->inner->delete($server);
    }
}

#[AsCommand(name: 'registration-probe:run')]
final class RegistrationProbeCommand extends Command {}

final readonly class RegistrationOperationObserved
{
    public function __construct(public string $identifier, public string $resourceUuid) {}
}
