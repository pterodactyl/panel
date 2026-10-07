<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Api\Application\AuthorizeExtensionApplicationRequest;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerParameterAccess;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\EnsureExtensionIsAvailable;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Services\Extensions\ExtensionActionDecorators;
use Pterodactyl\Services\Extensions\ExtensionConsoleRegistry;
use Pterodactyl\Services\Extensions\ExtensionHeadTags;
use Pterodactyl\Services\Extensions\ExtensionManager;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingsRegistry;
use Throwable;

/**
 * Base class every extension's service provider extends. The protected helpers are
 * the curated backend API surface: routes mounted at the extension's namespaced
 * prefixes with the same middleware stacks core routes use, migrations, views,
 * translations, and typed per-extension settings.
 * Routes (including `Route::bind`, `Route::model` and `Route::pattern` calls in route
 * files), operation listeners, action wrappers, commands, schedules, head tags, settings
 * and permissions activate after successful provider boot. Direct container mutations
 * and other PHP side effects are not staged.
 *
 * When register() or boot() throws, the extension is recorded as failed: nothing staged
 * through these helpers activates, and routes added directly with the Route facade are
 * removed again unless the panel's routes are cached. Everything else the provider did
 * before it threw stays in effect for the rest of the process, because neither PHP nor
 * Laravel can undo it: its classes stay loaded, Laravel keeps the provider instance in its
 * provider list, and container bindings and extenders, event listeners (view composers
 * and model observers included), gates, macros, middleware aliases, and commands or
 * schedules added without these helpers keep working. The provider is loaded again by the
 * next request or worker, so a provider that keeps failing leaves the same side effects
 * behind each time.
 */
abstract class ExtensionProvider extends ServiceProvider
{
    private ?ExtensionRegistration $registration = null;

    public function __construct($app, protected ExtensionManifest $extension)
    {
        parent::__construct($app);
    }

    final public function beginRegistration(): void
    {
        $this->registration()->begin();
    }

    final public function commitRegistration(): void
    {
        $this->registration()->commit($this->id());
    }

    final public function discardRegistration(): void
    {
        $this->registration()->discard();
    }

    protected function id(): string
    {
        return $this->extension->id;
    }

    /** Absolute path inside the extension's package directory. */
    protected function extensionPath(string ...$parts): string
    {
        return $this->extension->path(...$parts);
    }

    /**
     * Mount a route file at /api/client/extensions/<id> with the client API
     * middleware stack (session/API-key auth, 2FA requirement, client throttle).
     * A route that declares a {server} parameter gets the same access and scoping
     * checks as server routes: the user must own the server, be one of its subusers
     * or be a root admin, otherwise the request is answered with a 404.
     */
    protected function registerClientApiRoutes(string $path): void
    {
        $this->registerRouteFile($path, ['api', RequireTwoFactorAuthentication::class, 'client-api', 'throttle:api.client', AuthenticateServerParameterAccess::class], '/api/client/extensions/'.$this->id(), 'client');
    }

    /**
     * Mount a route file at /api/application/extensions/<id> with the application
     * API middleware stack. Extension routes have no API key resource of their own,
     * so an application API key must grant read access to every resource for GET,
     * HEAD and OPTIONS requests and write access to every resource for any other
     * method, otherwise the request is refused with a 403. Root admins using the
     * panel session or an account API key pass, as they do on core endpoints.
     */
    protected function registerApplicationApiRoutes(string $path): void
    {
        $this->registerRouteFile($path, ['api', RequireTwoFactorAuthentication::class, 'application-api', 'throttle:api.application', AuthorizeExtensionApplicationRequest::class], '/api/application/extensions/'.$this->id(), 'application');
    }

    /**
     * Mount a route file at /api/client/servers/{server}/extensions/<id> with the
     * full server-scoped client stack: the client API middleware plus the same
     * server subject/access/scoping middleware core server routes use. Routes in
     * the file receive the resolved server via route-model binding - type-hint
     * Pterodactyl\Models\Server on controller actions.
     *
     * The stack only checks that the user can see the server: the owner, root
     * admins and every subuser pass, whatever permissions the subuser holds. As on
     * core endpoints, each route must check its own permission, for example
     * `$request->user()->can('ext.<id>.<key>', $server)` for a key registered with
     * registerPermissions(), or a core permission.
     */
    protected function registerServerApiRoutes(string $path): void
    {
        $this->registerRouteFile($path, [
            'api',
            RequireTwoFactorAuthentication::class,
            'client-api',
            'throttle:api.client',
            ServerSubject::class,
            AuthenticateServerAccess::class,
            ResourceBelongsToServer::class,
        ], '/api/client/servers/{server}/extensions/'.$this->id(), 'server');
    }

    /**
     * Mount a route file at /api/admin/extensions/<id> with the admin API
     * middleware stack (root administrator auth and admin throttle).
     */
    protected function registerAdminApiRoutes(string $path): void
    {
        $this->registerRouteFile($path, ['api', RequireTwoFactorAuthentication::class, 'admin-api', 'throttle:api.admin'], '/api/admin/extensions/'.$this->id(), 'admin');
    }

    /**
     * Register conventional API route files from routes/{client,server,admin,application}.php.
     * Extensions with custom routing needs can keep calling the explicit helpers.
     */
    protected function registerApiRoutes(?string $directory = null): void
    {
        $directory ??= $this->extensionPath('routes');

        foreach ([
            'client.php' => $this->registerClientApiRoutes(...),
            'server.php' => $this->registerServerApiRoutes(...),
            'admin.php' => $this->registerAdminApiRoutes(...),
            'application.php' => $this->registerApplicationApiRoutes(...),
        ] as $file => $register) {
            $path = mb_rtrim($directory, '/\\').DIRECTORY_SEPARATOR.$file;
            if (is_file($path)) {
                $register($path);
            }
        }
    }

    /**
     * Mount a route file at /extensions/<id> in the web group. Authentication is the
     * extension's choice - add auth middleware inside the route file as needed.
     */
    protected function registerWebRoutes(string $path): void
    {
        $this->registerRouteFile($path, ['web'], '/extensions/'.$this->id(), 'web', scopeBindings: false);
    }

    /**
     * Mount a route file at /extensions/<id> with the same authenticated web
     * middleware stack core user routes use.
     */
    protected function registerAuthenticatedWebRoutes(string $path): void
    {
        $this->registerRouteFile($path, ['web', 'auth', 'auth.session', RequireTwoFactorAuthentication::class], '/extensions/'.$this->id(), 'web', scopeBindings: false);
    }

    /**
     * Mount a route file under a top-level URL prefix the manifest claims in
     * `routes.root`, e.g. `"routes": {"root": ["go"]}` serves the file at /go. Without
     * `$prefix` the file is mounted under every claimed prefix; anything the manifest
     * does not declare is refused. Routes run in the web group, are rate limited per
     * client (`throttle:extensions.root`), and are public unless the route file adds
     * auth middleware. They are named `extensions.<id>.root.<prefix>.<name>`.
     *
     * @throws InvalidExtensionException
     */
    protected function registerRootRoutes(string $path, ?string $prefix = null): void
    {
        $prefixes = $prefix === null ? $this->extension->rootPrefixes : [$prefix];
        throw_if($prefixes === [], InvalidExtensionException::class, sprintf('Extension "%s" declares no "routes.root" prefixes in its manifest.', $this->id()));

        foreach ($prefixes as $claimed) {
            throw_unless(in_array($claimed, $this->extension->rootPrefixes, true), InvalidExtensionException::class, sprintf('Extension "%s" does not declare the root path prefix "%s" in its manifest.', $this->id(), $claimed));
            $this->registerRouteFile($path, ['web', 'throttle:extensions.root'], '/'.$claimed, 'root.'.$claimed, scopeBindings: false);
        }
    }

    /**
     * Register the extension's database/migrations directory with the migrator. The panel
     * already does this for every enabled extension, since enabling one runs its migrations;
     * calling it again changes nothing.
     */
    protected function loadExtensionMigrations(): void
    {
        $this->loadMigrationsFrom($this->extensionPath('database', 'migrations'));
    }

    /** Blade views under resources/views, namespaced as ext-<id>::view. */
    protected function loadExtensionViews(): void
    {
        $this->loadViewsFrom($this->extensionPath('resources', 'views'), 'ext-'.$this->id());
    }

    /** Translations under resources/lang, namespaced as ext-<id>::key. */
    protected function loadExtensionTranslations(): void
    {
        $this->loadTranslationsFrom($this->extensionPath('resources', 'lang'), 'ext-'.$this->id());
    }

    /**
     * Listen for completed server operations: `provision`, `install`, `reinstall`,
     * `backup`, `delete`, `suspend`, `unsuspend` and `transfer`. The event is immutable,
     * carries identifiers only (after `delete` the server row is gone) and is dispatched
     * once the surrounding transaction has committed.
     *
     * @param  callable(OperationCompleted): void  $listener
     */
    protected function listenToServerOperations(callable $listener): void
    {
        $registration = $this->registration();
        $registration->defer(function () use ($listener, $registration): void {
            Event::listen(OperationCompleted::class, function (OperationCompleted $event) use ($listener, $registration): void {
                if (! $registration->isActive()) {
                    return;
                }

                try {
                    $listener($event);
                } catch (Throwable $throwable) {
                    $this->app->make(ExtensionRepository::class)->recordFailure($this->id(), $throwable->getMessage(), $throwable, 'event');
                }
            });
        });
    }

    /**
     * Run code around a core action. `$contract` is an action contract under
     * `Pterodactyl\Contracts\` that the panel binds; `$decorator` receives the
     * implementation that would otherwise be used and returns an implementation of the
     * same contract, normally a small class of the extension that holds the inner action
     * and calls it. Every caller that resolves the contract afterwards gets the wrapper.
     *
     * The wrapper owns the call: it decides whether and when to invoke the inner action
     * and must forward the contract's fluent options itself (`DeletesServers::withForce()`
     * returns the wrapper after passing the flag on to the inner action). Whatever it
     * throws reaches the caller unchanged - the panel never swallows or rewrites it - and
     * is recorded against the extension when it is reported. Exceptions of the inner
     * action pass through the same way and stay core's own.
     *
     * The decorator is skipped while the extension is disabled or failed to boot, and a
     * decorator that throws or returns anything else is recorded and skipped, so the core
     * action always stays resolvable. Extensions wrap in load order; the last is outermost.
     *
     * The contracts under `Pterodactyl\Contracts\Extensions\` and `Pterodactyl\Contracts\Themes\`
     * (installing, enabling, disabling, removing and configuring extensions, applying themes)
     * cannot be wrapped: asking for one fails the provider's boot like any other contract
     * that cannot be wrapped.
     *
     * @template TContract of object
     *
     * @param  class-string<TContract>  $contract
     * @param  Closure(TContract): TContract  $decorator
     */
    protected function wrapAction(string $contract, Closure $decorator): void
    {
        $registration = $this->registration();
        $registration->defer(function () use ($contract, $decorator, $registration): void {
            $this->app->make(ExtensionActionDecorators::class)->register($this->id(), $contract, $decorator, $registration);
        });
    }

    /**
     * Register artisan commands. They exist only while the extension is enabled and
     * booted successfully. A command's name and aliases must start with the extension id
     * (`<id>:clean`) and must not already exist: a command that breaks either rule is
     * left out and recorded against the extension.
     *
     * @param  list<class-string>  $classes
     */
    protected function registerCommands(array $classes): void
    {
        $this->registration()->defer(function () use ($classes): void {
            $this->app->make(ExtensionConsoleRegistry::class)->registerCommands($this->id(), $classes);
        });
    }

    /**
     * Define scheduled tasks on the panel's scheduler, exactly as core does:
     * `$schedule->command('<id>:clean')->daily()`. Tasks only run while the extension is
     * enabled, and a failed run is recorded against the extension.
     *
     * @param  Closure(Schedule): void  $callback
     */
    protected function registerSchedule(Closure $callback): void
    {
        $this->registration()->defer(function () use ($callback): void {
            $this->app->make(ExtensionConsoleRegistry::class)->registerSchedule($this->id(), $callback);
        });
    }

    /**
     * Add `<meta>` and `<link>` elements to the panel's document head, for guests and
     * signed-in users alike. Each entry is `['tag' => 'meta'|'link', <attribute> => <value>]`
     * with attributes from a fixed allow-list (meta: name or property, content, media;
     * link: rel, href, sizes, type, media, color, crossorigin, hreflang, title). `href`
     * must be an http(s) URL or a path on this panel, `rel` is limited to relations that
     * load nothing executable, and scripts, stylesheets, inline styles and event
     * handlers cannot be expressed. An element the panel also ships (theme-color, the
     * web app manifest, the favicons) replaces the panel's.
     *
     * Pass a closure for values that depend on settings: it runs when the layout renders
     * and its result is cached for `extensions.head_tags_cache_seconds`.
     *
     * @param  array<int, array<string, string>>|(Closure(): array<int, array<string, string>>)  $tags
     */
    protected function registerHeadTags(array|Closure $tags): void
    {
        $this->registration()->defer(function () use ($tags): void {
            $this->app->make(ExtensionHeadTags::class)->register($this->id(), $this->extension->version, $tags);
        });
    }

    /** Typed key/value settings scoped to this extension. */
    protected function settings(): ExtensionSettings
    {
        return $this->app->make(ExtensionManager::class)->settings($this->id());
    }

    /**
     * Register the extension's typed settings definition with the panel. The
     * admin Extensions UI auto-renders a settings form from it (labels, fields,
     * validation - no extension frontend code needed), the panel serves
     * GET/PATCH /api/admin/extensions/<id>/settings for it (that path segment
     * is reserved under the admin prefix), and any definition marked
     * ->frontend() is delivered to the extension bundle as ctx.config.
     */
    protected function registerSettings(ExtensionSettingsDefinition $definition): void
    {
        $this->registration()->defer(function () use ($definition): void {
            $this->app->make(ExtensionSettingsRegistry::class)->register($this->id(), $definition);
        });
    }

    /**
     * Register subuser permissions for this extension. Each key becomes the permission
     * `ext.<id>.<key>`: it appears in the server subuser editor under the given group
     * description, can be granted to subusers, gates server screens through the manifest's
     * `permission` list, and is checked on the backend with
     * `$user->can('ext.<id>.<key>', $server)`.
     *
     * @param  array<string, string>  $keys  permission key => description
     */
    protected function registerPermissions(string $description, array $keys): void
    {
        $this->registration()->defer(function () use ($description, $keys): void {
            $this->app->make(ExtensionPermissionRegistry::class)->register($this->id(), $description, $keys);
        });
    }

    /** @param list<class-string|string> $middleware */
    private function registerRouteFile(string $path, array $middleware, string $prefix, string $area, bool $scopeBindings = true): void
    {
        $this->registration()->defer(function () use ($path, $middleware, $prefix, $area, $scopeBindings): void {
            $routes = Route::middleware([...$middleware, EnsureExtensionIsAvailable::class.':'.$this->id()])
                ->prefix($prefix)
                ->name('extensions.'.$this->id().'.'.$area.'.');
            if ($scopeBindings) {
                $routes->scopeBindings();
            }

            $routes->group(fn () => $this->loadRoutesFrom($path));
        });
    }

    private function registration(): ExtensionRegistration
    {
        return $this->registration ??= $this->app->make(ExtensionRegistration::class);
    }
}
