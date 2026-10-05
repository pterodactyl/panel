<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Extensions\ExtensionProviderRuntimeTest;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Extensions\ExtensionProvider;
use Pterodactyl\Http\Middleware\EnsureExtensionIsAvailable;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Extensions\ExtensionConsoleRegistry;
use Pterodactyl\Services\Extensions\ExtensionHeadTags;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Tests\TestCase;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function pterodactylTestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-extension-runtime-'.uniqid();
    File::ensureDirectoryExists($this->directory);
    config(['extensions.enabled' => true]);
    $this->available = [];
    $this->failures = [];
    $this->app->bind(DeletesServers::class, CoreDeleter::class);
    // The layout reads the built Vite manifest; use the committed fixture instead.
    $this->app->bind(AssetHashService::class, fn (): AssetHashService => new AssetHashService($this->app->make(FilesystemManager::class), base_path('tests/Fixtures')));
    CoreDeleter::$deleted = [];
});

afterEach(function (): void {
    File::deleteDirectory($this->directory);
});

test('a wrapped action runs extension code around the core action and honours fluent options', function (): void {
    extensions('dns');
    provider('dns')->wrap(DeletesServers::class, fn (DeletesServers $inner): DeletesServers => new RecordingDeleter($inner));

    $action = $this->app->make(DeletesServers::class);
    expect($action)->toBeInstanceOf(RecordingDeleter::class);
    expect($action->withForce())->toBe($action);
    $action->delete(new Server);

    expect(CoreDeleter::$deleted)->toBe(['before', 'forced', 'after']);
    expect($this->failures)->toBe([]);
});

test('extensions wrap in load order and each resolution is decorated afresh', function (): void {
    extensions('first', 'second');
    provider('first')->wrap(DeletesServers::class, fn (DeletesServers $inner): DeletesServers => new RecordingDeleter($inner, 'first'));
    provider('second')->wrap(DeletesServers::class, fn (DeletesServers $inner): DeletesServers => new RecordingDeleter($inner, 'second'));

    $this->app->make(DeletesServers::class)->delete(new Server);
    $this->app->make(DeletesServers::class)->delete(new Server);

    expect(CoreDeleter::$deleted)->toBe([
        'second:before', 'first:before', 'deleted', 'first:after', 'second:after',
        'second:before', 'first:before', 'deleted', 'first:after', 'second:after',
    ]);
});

test('a wrapper is inert while its extension is not available', function (): void {
    extensions();
    provider('dns')->wrap(DeletesServers::class, fn (DeletesServers $inner): DeletesServers => new RecordingDeleter($inner));

    expect($this->app->make(DeletesServers::class))->toBeInstanceOf(CoreDeleter::class);

    extensions('dns');
    expect($this->app->make(DeletesServers::class))->toBeInstanceOf(RecordingDeleter::class);
});

test('a wrapper staged by a provider that never commits or fails to commit is inert', function (): void {
    extensions('dns');
    $discarded = provider('dns');
    $discarded->beginRegistration();
    $discarded->wrap(DeletesServers::class, fn (DeletesServers $inner): DeletesServers => new RecordingDeleter($inner));
    $discarded->discardRegistration();
    expect($this->app->make(DeletesServers::class))->toBeInstanceOf(CoreDeleter::class);

    $failed = provider('dns');
    $failed->beginRegistration();
    $failed->wrap(DeletesServers::class, fn (DeletesServers $inner): DeletesServers => new RecordingDeleter($inner));
    $failed->consoleCommands([ProbeCommand::class]);
    $failed->schedule(function (Schedule $schedule): void {
        $schedule->call(fn (): null => null);
    });
    $failed->headTags([['tag' => 'meta', 'name' => 'theme-color', 'content' => '#000000']]);
    $failed->permissions('Broken.', []);
    expect(fn () => $failed->commitRegistration())->toThrow(InvalidArgumentException::class);

    expect($this->app->make(DeletesServers::class))->toBeInstanceOf(CoreDeleter::class);
    expect($this->app->make(ExtensionConsoleRegistry::class)->snapshot())->toBe(['commands' => [], 'schedules' => []]);
    expect($this->app->make(ExtensionHeadTags::class)->toHtml())->toBe('');
});

test('a broken decorator is recorded and the core action stays resolvable', function (Closure $decorator, string $message): void {
    extensions('dns');
    provider('dns')->wrap(DeletesServers::class, $decorator);

    expect($this->app->make(DeletesServers::class))->toBeInstanceOf(CoreDeleter::class);
    expect($this->failures)->toBe([['dns', $message, 'action']]);
})->with([
    'throws' => [fn (): never => throw new RuntimeException('decorator failed'), 'decorator failed'],
    'returns another type' => [fn (): Server => new Server, 'The decorator for '.DeletesServers::class.' must return an implementation of that contract.'],
]);

test('exceptions of a wrapper and of the core action reach the caller unchanged', function (): void {
    extensions('dns');
    provider('dns')->wrap(DeletesServers::class, fn (DeletesServers $inner): DeletesServers => new RecordingDeleter($inner, failure: new RuntimeException('dns cleanup failed')));
    expect(fn () => $this->app->make(DeletesServers::class)->delete(new Server))->toThrow(RuntimeException::class, 'dns cleanup failed');

    CoreDeleter::$failure = $core = new RuntimeException('wings is unreachable');
    try {
        $this->app->make(DeletesServers::class)->delete(new Server);
        $this->fail('The core exception must propagate.');
    } catch (Throwable $throwable) {
        expect($throwable)->toBe($core);
    } finally {
        CoreDeleter::$failure = null;
    }
});

test('only bound panel contracts can be wrapped', function (string $contract): void {
    extensions('dns');
    expect(fn () => provider('dns')->wrap($contract, fn ($inner) => $inner))->toThrow(InvalidArgumentException::class, 'can only wrap action contracts');
})->with([
    'a concrete action' => [CoreDeleter::class],
    'a framework contract' => [\Illuminate\Contracts\Cache\Repository::class],
    'a missing interface' => [DeletesServers::class.'Missing'],
    'an unbound panel interface' => [\Pterodactyl\Contracts\Models\Identifiable::class],
    'a shared panel service' => [\Pterodactyl\Contracts\Extensions\HashidsInterface::class],
    'an interface outside the contracts namespace' => [\Pterodactyl\Services\Extensions\Contracts\ManagesExtensionSettings::class],
]);

test('registered commands reach artisan only for available extensions', function (): void {
    extensions('probe');
    provider('probe')->consoleCommands([ProbeCommand::class]);
    provider('disabled')->consoleCommands([DisabledProbeCommand::class]);

    expect(Artisan::all())->toHaveKey('probe:run')->not->toHaveKey('disabled:run');
    expect(Artisan::call('probe:run'))->toBe(7);
});

test('only console commands can be registered', function (): void {
    expect(fn () => provider('probe')->consoleCommands([Server::class]))->toThrow(InvalidArgumentException::class, 'is not a console command');
});

test('scheduled tasks run only while the extension is available and failures are attributed', function (): void {
    extensions('probe');
    provider('probe')->schedule(function (Schedule $schedule): void {
        $schedule->call(fn (): never => throw new RuntimeException('task failed'))->everyMinute()->description('probe task');
    });
    provider('disabled')->schedule(function (Schedule $schedule): void {
        $schedule->call(fn (): null => null)->everyMinute()->description('disabled task');
    });
    $schedule = new Schedule;

    $this->app->make(ExtensionConsoleRegistry::class)->schedule($schedule);

    expect($schedule->events())->toHaveCount(1);
    $event = $schedule->events()[0];
    expect($event->description)->toBe('probe task');
    expect($event->filtersPass($this->app))->toBeTrue();
    expect(fn () => $event->run($this->app))->toThrow(RuntimeException::class, 'task failed');
    expect($this->failures)->toBe([['probe', 'Scheduled task "probe task" failed.', 'schedule']]);

    extensions();
    expect($event->filtersPass($this->app))->toBeFalse();
});

test('a schedule callback that throws is recorded and schedules nothing runnable', function (): void {
    extensions('probe');
    provider('probe')->schedule(function (Schedule $schedule): void {
        $schedule->call(fn (): null => null)->everyMinute();

        throw new RuntimeException('schedule failed');
    });
    $schedule = new Schedule;

    $this->app->make(ExtensionConsoleRegistry::class)->schedule($schedule);

    expect($this->failures)->toBe([['probe', 'schedule failed', 'schedule']]);
    expect($schedule->events()[0]->filtersPass($this->app))->toBeFalse();
});

test('head tags are rendered into the layout for guests and replace the panel defaults', function (): void {
    extensions('pwa');
    provider('pwa')->headTags([
        ['tag' => 'link', 'rel' => 'manifest', 'href' => '/extensions/pwa/manifest.webmanifest'],
        ['tag' => 'meta', 'name' => 'theme-color', 'content' => '#0f172a"><script>'],
    ]);
    provider('disabled')->headTags([['tag' => 'meta', 'name' => 'application-name', 'content' => 'Disabled']]);

    $html = view('templates/base.core')->render();

    expect($html)
        ->toContain('<link rel="manifest" href="/extensions/pwa/manifest.webmanifest">')
        ->toContain('<meta name="theme-color" content="#0f172a&quot;&gt;&lt;script&gt;">')
        ->not->toContain('/favicons/manifest.json')
        ->not->toContain('#0e4688')
        ->not->toContain('Disabled')
        ->toContain('/favicons/favicon.ico');
});

test('head tag callbacks are resolved when rendered, cached, and never break the page', function (): void {
    extensions('pwa', 'broken');
    $calls = 0;
    provider('pwa')->headTags(function () use (&$calls): array {
        $calls++;

        return [['tag' => 'meta', 'name' => 'apple-mobile-web-app-title', 'content' => 'Panel '.$calls]];
    });
    provider('broken')->headTags(fn (): array => [['tag' => 'script', 'src' => 'https://example.com/x.js']]);
    $tags = $this->app->make(ExtensionHeadTags::class);

    expect($calls)->toBe(0);
    expect($tags->toHtml())->toBe('<meta name="apple-mobile-web-app-title" content="Panel 1">');
    $tags->restore($tags->snapshot());
    expect($tags->toHtml())->toBe('<meta name="apple-mobile-web-app-title" content="Panel 1">');
    expect($calls)->toBe(1);
    expect($this->failures)->toBe([['broken', 'Head tags must set "tag" to "meta" or "link".', 'head']]);

    $tags->forget('pwa');
    expect($tags->toHtml())->toBe('<meta name="apple-mobile-web-app-title" content="Panel 2">');

    config(['extensions.head_tags_cache_seconds' => 0]);
    Cache::flush();
    $tags->forget('pwa');
    $tags->toHtml();
    $tags->forget('pwa');
    $tags->toHtml();
    expect($calls)->toBe(4);
});

test('invalid static head tags fail the registration', function (): void {
    expect(fn () => provider('pwa')->headTags([['tag' => 'link', 'rel' => 'stylesheet', 'href' => '/x.css']]))->toThrow(InvalidArgumentException::class);
});

test('root routes mount under each claimed prefix ahead of the SPA catch-all', function (): void {
    extensions('redirect');
    $path = writeRouteFile('root.php', '/{slug}', 'go');
    provider('redirect', ['routes' => ['root' => ['go', 'r']]])->rootRoutes($path);
    Route::getRoutes()->refreshNameLookups();

    foreach (['go', 'r'] as $prefix) {
        $route = Route::getRoutes()->getByName('extensions.redirect.root.'.$prefix.'.go');
        expect($route)->not->toBeNull();
        expect($route->uri())->toBe($prefix.'/{slug}');
        expect($route->middleware())->toBe(['web', 'throttle:extensions.root', EnsureExtensionIsAvailable::class.':redirect']);
        expect(Route::getRoutes()->match(Request::create('/'.$prefix.'/docs'))->getName())->toBe('extensions.redirect.root.'.$prefix.'.go');
    }

    expect(Route::getRoutes()->match(Request::create('/server/abc'))->uri())->toBe('{react}');
    $this->get('/go/docs')->assertOk()->assertContent('ok');
    extensions();
    $this->get('/go/docs')->assertNotFound();
});

test('authenticated web routes turn guests away', function (): void {
    extensions('account');
    $path = writeRouteFile('account.php', '/link', 'link');
    provider('account')->authenticatedWebRoutes($path);
    Route::getRoutes()->refreshNameLookups();

    expect(Route::getRoutes()->getByName('extensions.account.web.link')->middleware())->toContain('auth');
    $this->get('/extensions/account/link')->assertRedirect('/auth/login');
    $this->getJson('/extensions/account/link')->assertUnauthorized();
});

test('a single claimed prefix can be mounted and undeclared prefixes are refused', function (): void {
    $path = writeRouteFile('root.php', '/{slug}', 'go');
    $provider = provider('redirect', ['routes' => ['root' => ['go', 'r']]]);
    $provider->rootRoutes($path, 'r');
    Route::getRoutes()->refreshNameLookups();

    expect(Route::has('extensions.redirect.root.r.go'))->toBeTrue();
    expect(Route::has('extensions.redirect.root.go.go'))->toBeFalse();
    expect(fn () => $provider->rootRoutes($path, 'links'))->toThrow(InvalidExtensionException::class, 'does not declare the root path prefix "links"');
    expect(fn () => provider('plain')->rootRoutes($path))->toThrow(InvalidExtensionException::class, 'declares no "routes.root" prefixes');
});

/** Marks the given extensions as enabled and collects every failure recorded against one. */
function extensions(string ...$identifiers): void
{
    (function () use ($identifiers): void {
        $this->available = $identifiers;
        if ($this->app->bound('extension-test.repository')) {
            return;
        }

        /** @var ExtensionRepository&MockInterface $repository */
        $repository = Mockery::mock(ExtensionRepository::class);
        $repository->shouldReceive('isAvailable')->andReturnUsing(fn (string $identifier): bool => in_array($identifier, $this->available, true));
        $repository->shouldReceive('enabled')->andReturnUsing(fn () => collect(array_fill_keys($this->available, true)));
        $repository->shouldReceive('recordFailure')->andReturnUsing(function (string $identifier, string $reason, ?Throwable $exception = null, string $phase = 'runtime'): void {
            $this->failures[] = [$identifier, $reason, $phase];
        });
        $this->app->instance(ExtensionRepository::class, $repository);
        $this->app->instance('extension-test.repository', true);
    })->call(pterodactylTestCase());
}

/** @param array<string, mixed> $manifest */
function provider(string $identifier, array $manifest = []): ExtensionProvider
{
    return (function () use ($identifier, $manifest) {
        if (! $this->app->bound('extension-test.repository')) {
            extensions();
        }

        $path = $this->directory.DIRECTORY_SEPARATOR.$identifier;
        File::ensureDirectoryExists($path);
        File::put($path.DIRECTORY_SEPARATOR.ExtensionManifest::FILENAME, json_encode(['id' => $identifier, 'name' => 'Runtime Fixture', 'version' => '1.0.0', ...$manifest], JSON_THROW_ON_ERROR));

        return new class($this->app, (new ExtensionManifestValidator)->fromDirectory($path)) extends ExtensionProvider
        {
            public function wrap(string $contract, Closure $decorator): void
            {
                $this->wrapAction($contract, $decorator);
            }

            public function consoleCommands(array $classes): void
            {
                $this->registerCommands($classes);
            }

            public function schedule(Closure $callback): void
            {
                $this->registerSchedule($callback);
            }

            public function headTags(array|Closure $tags): void
            {
                $this->registerHeadTags($tags);
            }

            public function rootRoutes(string $path, ?string $prefix = null): void
            {
                $this->registerRootRoutes($path, $prefix);
            }

            public function authenticatedWebRoutes(string $path): void
            {
                $this->registerAuthenticatedWebRoutes($path);
            }

            public function permissions(string $description, array $keys): void
            {
                $this->registerPermissions($description, $keys);
            }
        };
    })->call(pterodactylTestCase());
}

function writeRouteFile(string $filename, string $uri, string $name): string
{
    return (function () use ($filename, $uri, $name): string {
        $path = $this->directory.DIRECTORY_SEPARATOR.$filename;
        File::put($path, <<<PHP
        <?php

        use Illuminate\\Support\\Facades\\Route;

        Route::get('{$uri}', fn () => 'ok')->name('{$name}');
        PHP);

        return $path;
    })->call(pterodactylTestCase());
}

final class CoreDeleter implements DeletesServers
{
    /** @var list<string> */
    public static array $deleted = [];

    public static ?Throwable $failure = null;

    private bool $force = false;

    public function withForce(bool $bool = true): self
    {
        $this->force = $bool;

        return $this;
    }

    public function delete(Server $server): void
    {
        throw_if(self::$failure instanceof Throwable, self::$failure);
        self::$deleted[] = $this->force ? 'forced' : 'deleted';
    }
}

/** What an extension ships: it holds the inner action, forwards fluent options and calls it. */
final class RecordingDeleter implements DeletesServers
{
    public function __construct(
        private readonly DeletesServers $inner,
        private readonly ?string $label = null,
        private readonly ?Throwable $failure = null,
    ) {}

    public function withForce(bool $bool = true): self
    {
        $this->inner->withForce($bool);

        return $this;
    }

    public function delete(Server $server): void
    {
        CoreDeleter::$deleted[] = $this->label === null ? 'before' : $this->label.':before';
        $this->inner->delete($server);
        throw_if($this->failure instanceof Throwable, $this->failure);
        CoreDeleter::$deleted[] = $this->label === null ? 'after' : $this->label.':after';
    }
}

#[AsCommand(name: 'probe:run')]
final class ProbeCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 7;
    }
}

#[AsCommand(name: 'disabled:run')]
final class DisabledProbeCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }
}
