<?php

namespace Pterodactyl\Http;

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Middleware\TrustProxies;
use Pterodactyl\Http\Middleware\TrimStrings;
use Illuminate\Session\Middleware\StartSession;
use Pterodactyl\Http\Middleware\EncryptCookies;
use Pterodactyl\Http\Middleware\Api\IsValidJson;
use Pterodactyl\Http\Middleware\VerifyCsrfToken;
use Pterodactyl\Http\Middleware\VerifyReCaptcha;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Pterodactyl\Http\Middleware\LanguageMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Pterodactyl\Http\Middleware\MaintenanceMiddleware;
use Pterodactyl\Http\Middleware\EnsureStatefulRequests;
use Pterodactyl\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Pterodactyl\Http\Middleware\Api\AuthenticateIPAccess;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Pterodactyl\Http\Middleware\Api\Daemon\DaemonAuthenticate;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Pterodactyl\Http\Middleware\Api\Client\SubstituteClientBindings;
use Pterodactyl\Http\Middleware\Api\Application\AuthenticateApplicationUser;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        CheckForMaintenanceMode::class,
        EncryptCookies::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            LanguageMiddleware::class,
            RequireTwoFactorAuthentication::class,
        ],
        'api' => [
            IsValidJson::class,
            EnsureStatefulRequests::class,
            'auth:sanctum',
            RequireTwoFactorAuthentication::class,
            AuthenticateIPAccess::class,
        ],
        'application-api' => [
            SubstituteBindings::class,
            AuthenticateApplicationUser::class,
        ],
        // TODO: don't allow an application key to use the client API, but do allow a client
        //  api key to access the application API.
        'client-api' => [SubstituteClientBindings::class],
        'daemon' => [
            SubstituteBindings::class,
            DaemonAuthenticate::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'guest' => RedirectIfAuthenticated::class,
        'admin' => AdminAuthenticate::class,
        'csrf' => VerifyCsrfToken::class,
        'throttle' => ThrottleRequests::class,
        'can' => Authorize::class,
        'bindings' => SubstituteBindings::class,
        'recaptcha' => VerifyReCaptcha::class,
        'node.maintenance' => MaintenanceMiddleware::class,
    ];
}
