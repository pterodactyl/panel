<?php

namespace Pterodactyl\Http;

use Pterodactyl\Http\Middleware\DaemonAuthenticate;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Middleware\SubstituteBindings;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Pterodactyl\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Pterodactyl\Http\Middleware\TrimStrings::class,

        /*
         * Custom middleware applied to all routes.
         */
        \Fideloper\Proxy\TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \Pterodactyl\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Pterodactyl\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Pterodactyl\Http\Middleware\LanguageMiddleware::class,
            \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication::class,
        ],
        'api' => [
            \Pterodactyl\Http\Middleware\HMACAuthorization::class,
            'throttle:60,1',
            'bindings',
        ],
        'daemon' => [
            \Pterodactyl\Http\Middleware\Daemon\DaemonAuthenticate::class,
            SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest' => \Pterodactyl\Http\Middleware\RedirectIfAuthenticated::class,
        'server' => \Pterodactyl\Http\Middleware\ServerAuthenticate::class,
        'subuser.auth' => \Pterodactyl\Http\Middleware\SubuserAccessAuthenticate::class,
        'subuser' => \Pterodactyl\Http\Middleware\Server\SubuserAccess::class,
        'admin' => \Pterodactyl\Http\Middleware\AdminAuthenticate::class,
        'daemon-old' => DaemonAuthenticate::class,
        'csrf' => \Pterodactyl\Http\Middleware\VerifyCsrfToken::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'recaptcha' => \Pterodactyl\Http\Middleware\VerifyReCaptcha::class,
        'schedule' => \Pterodactyl\Http\Middleware\Server\ScheduleAccess::class,
    ];
}
