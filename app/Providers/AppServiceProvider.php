<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use Pterodactyl\Enum\ResourceLimit;
use Pterodactyl\Extensions\Illuminate\Auth\Passwords\PasswordBrokerManager;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Tag;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Services\Daemon\DaemonManager;
use Pterodactyl\Support\JsonValueGuard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Route::model('database', Database::class);

        $this->configureRateLimiting();

        // Surface lazy loads, discarded fills, and missing-attribute reads as
        // exceptions everywhere except production, where a missed spot should log a
        // query rather than take the panel down.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Refuse migrate:fresh and friends against the production database.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // All framework-created dates (model casts included) are immutable; in-place
        // mutation of a shared timestamp is never something we want.
        Date::use(CarbonImmutable::class);

        View::share('appVersion', $this->versionData()['version']);
        View::share('appIsGit', $this->versionData()['is_git']);

        Paginator::useBootstrap();

        // If the APP_URL value is set with https:// make sure we force it here. Theoretically
        // this should just work with the proxy logic, but there are a lot of cases where it
        // doesn't, and it triggers a lot of support requests, so lets just head it off here.
        //
        // @see https://github.com/pterodactyl/panel/issues/3623
        if (Str::startsWith(JsonValueGuard::nullableString(config('app.url')) ?? '', 'https://')) {
            URL::forceScheme('https');
        }

        Relation::enforceMorphMap([
            'allocation' => Allocation::class,
            'api_key' => ApiKey::class,
            'backup' => Backup::class,
            'database' => Database::class,
            'database_host' => DatabaseHost::class,
            'egg' => Egg::class,
            'egg_variable' => EggVariable::class,
            'location' => Location::class,
            'mount' => Mount::class,
            'node' => Node::class,
            'schedule' => Schedule::class,
            'server' => Server::class,
            'server_database' => Database::class,
            'ssh_key' => UserSSHKey::class,
            'tag' => Tag::class,
            'task' => Task::class,
            'user' => User::class,
        ]);
    }

    /**
     * Register application service providers.
     */
    public function register(): void
    {
        $this->app->singleton(DaemonManager::class);

        $this->app->extend('auth.password', fn (): PasswordBrokerManager => new PasswordBrokerManager($this->app));

        $this->app->bind(function (): Fractal {
            $requested = JsonValueGuard::stringList(array_values($this->app->make(Request::class)->collect('include')->all()));

            $includes = collect($requested)
                ->flatMap(fn (string $value): array => explode(',', $value))
                ->map(fn (string $value): string => mb_trim($value))
                ->filter()
                ->values()
                ->all();

            return (new Fractal(new Manager))->parseIncludes($includes)->limitRecursion(2);
        });

        // Only load the settings service provider if the environment
        // is configured to allow it.
        if (! config('pterodactyl.load_environment_only', false) && ! $this->app->environment('testing')) {
            $this->app->register(SettingsServiceProvider::class);
        }
    }

    /**
     * Return version information for the footer.
     *
     * @return array{version: string, is_git: bool}
     */
    protected function versionData(): array
    {
        return Cache::remember('git-version', 5, function (): array {
            if (file_exists(base_path('.git/HEAD'))) {
                $headContents = file_get_contents(base_path('.git/HEAD'));

                if ($headContents !== false) {
                    $head = explode(' ', $headContents);

                    if (array_key_exists(1, $head)) {
                        $path = base_path('.git/'.mb_trim($head[1]));
                    }
                }
            }

            if (isset($path) && file_exists($path)) {
                $refContents = file_get_contents($path);

                if ($refContents !== false) {
                    return [
                        'version' => mb_substr($refContents, 0, 8),
                        'is_git' => true,
                    ];
                }
            }

            return [
                'version' => config()->string('app.version'),
                'is_git' => false,
            ];
        });
    }

    /**
     * Rate limiters for authentication and the three APIs.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('authentication', function (Request $request): Limit {
            if ($request->route()?->named('auth.post.forgot-password')) {
                return Limit::perMinute(2)->by($request->ip());
            }

            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api.client', fn (Request $request): Limit => $this->apiLimit($request, 'client'));
        RateLimiter::for('api.application', fn (Request $request): Limit => $this->apiLimit($request, 'application'));
        RateLimiter::for('api.admin', fn (Request $request): Limit => $this->apiLimit($request, 'admin'));

        ResourceLimit::boot();
    }

    private function apiLimit(Request $request, string $name): Limit
    {
        $user = $request->user();
        $key = $user instanceof User ? $user->uuid : $request->ip();

        return Limit::perMinutes(
            JsonValueGuard::integer(config("http.rate_limit.{$name}_period")),
            JsonValueGuard::integer(config("http.rate_limit.{$name}")),
        )->by($key);
    }
}
