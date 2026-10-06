<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Console\Kernel;
use Pterodactyl\Console\Scheduler;
use Pterodactyl\Exceptions\ApiErrorResponse;
use Pterodactyl\Http\Middleware\Activity\TrackAPIKey;
use Pterodactyl\Http\Middleware\Api\Admin\AuthenticateAdmin;
use Pterodactyl\Http\Middleware\Api\Application\AuthenticateApplicationUser;
use Pterodactyl\Http\Middleware\Api\AuthenticateIPAccess;
use Pterodactyl\Http\Middleware\Api\Client\RequireClientApiKey;
use Pterodactyl\Http\Middleware\Api\Client\SubstituteClientBindings;
use Pterodactyl\Http\Middleware\Api\Daemon\DaemonAuthenticate;
use Pterodactyl\Http\Middleware\Api\IsValidJson;
use Pterodactyl\Http\Middleware\EnsureStatefulRequests;
use Pterodactyl\Http\Middleware\LanguageMiddleware;
use Pterodactyl\Http\Middleware\MaintenanceMiddleware;
use Pterodactyl\Http\Middleware\RedirectIfAuthenticated;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Http\Middleware\SetSecurityHeaders;
use Pterodactyl\Http\Middleware\VerifyReCaptcha;
use Symfony\Component\HttpKernel\Exception\HttpException;

$app = Application::configure(basePath: $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__))
    ->withRouting(using: function (): void {
        Route::middleware('web')->group(function (): void {
            Route::middleware(['auth.session', RequireTwoFactorAuthentication::class])
                ->group(base_path('routes/base.php'));

            Route::middleware('guest')->prefix('/auth')->group(base_path('routes/auth.php'));
        });

        Route::middleware(['api', RequireTwoFactorAuthentication::class])->group(function (): void {
            Route::middleware(['application-api', 'throttle:api.application'])
                ->prefix('/api/application')
                ->scopeBindings()
                ->group(base_path('routes/api-application.php'));

            Route::middleware(['client-api', 'throttle:api.client'])
                ->prefix('/api/client')
                ->scopeBindings()
                ->group(base_path('routes/api-client.php'));

            Route::middleware(['admin-api', 'throttle:api.admin'])
                ->prefix('/api/admin')
                ->scopeBindings()
                ->group(base_path('routes/api-admin.php'));
        });

        Route::middleware('daemon')
            ->prefix('/api/remote')
            ->scopeBindings()
            ->group(base_path('routes/api-remote.php'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SetSecurityHeaders::class);

        $middleware->trimStrings(except: [
            'password',
            'password_confirmation',
            fn (Request $request): bool => $request->is('api/client/servers/*/files', 'api/client/servers/*/files/*'),
        ]);

        $middleware->preventRequestForgery(except: ['remote/*', 'daemon/*']);

        $middleware->redirectGuestsTo('/auth/login');

        $middleware->priority([SubstituteClientBindings::class]);

        $middleware->web(append: LanguageMiddleware::class);

        $middleware->group('api', [
            EnsureStatefulRequests::class,
            'auth:sanctum',
            IsValidJson::class,
            TrackAPIKey::class,
            RequireTwoFactorAuthentication::class,
            AuthenticateIPAccess::class,
        ]);

        $middleware->group('application-api', [SubstituteBindings::class, AuthenticateApplicationUser::class]);
        $middleware->group('client-api', [SubstituteClientBindings::class, RequireClientApiKey::class]);
        $middleware->group('admin-api', [SubstituteBindings::class, AuthenticateAdmin::class]);
        $middleware->group('daemon', [SubstituteBindings::class, DaemonAuthenticate::class]);

        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
            'recaptcha' => VerifyReCaptcha::class,
            'node.maintenance' => MaintenanceMiddleware::class,
        ]);
    })
    ->withSchedule(new Scheduler)
    ->withExceptions(function (Exceptions $exceptions): void {
        if (config('app.exceptions.report_all', false)) {
            $exceptions->stopIgnoring([
                AuthenticationException::class,
                AuthorizationException::class,
                HttpException::class,
                ModelNotFoundException::class,
                TokenMismatchException::class,
                ValidationException::class,
            ]);
        }

        $exceptions->dontFlash(['token', 'secret', 'password', 'password_confirmation']);

        $exceptions->render(fn (ValidationException $e, Request $request): ?JsonResponse => $request->expectsJson() ? ApiErrorResponse::validation($e) : null);

        $exceptions->render(fn (AuthenticationException $e, Request $request): JsonResponse|RedirectResponse => $request->expectsJson()
            ? ApiErrorResponse::render($e)
            : redirect()->guest('/auth/login'));

        $exceptions->render(fn (Throwable $e, Request $request): ?JsonResponse => $request->expectsJson() && ! $e instanceof HttpResponseException
            ? ApiErrorResponse::render($e)
            : null);
    })
    ->create();

// The panel's console kernel adds extension commands after its own. Artisan resolves the
// kernel before any provider registers, so it is bound here rather than in a provider.
$app->singleton(ConsoleKernelContract::class, Kernel::class);

if (isset($_ENV['APP_STORAGE_PATH'])) {
    $app->useStoragePath($_ENV['APP_STORAGE_PATH']);
}

return $app;
