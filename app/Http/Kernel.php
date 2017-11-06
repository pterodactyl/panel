<?php

namespace Pterodactyl\Http;

use Fideloper\Proxy\TrustProxies;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\Authenticate;
use Pterodactyl\Http\Middleware\TrimStrings;
use Illuminate\Session\Middleware\StartSession;
use Pterodactyl\Http\Middleware\EncryptCookies;
use Pterodactyl\Http\Middleware\VerifyCsrfToken;
use Pterodactyl\Http\Middleware\VerifyReCaptcha;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Pterodactyl\Http\Middleware\HMACAuthorization;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Pterodactyl\Http\Middleware\LanguageMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Pterodactyl\Http\Middleware\AccessingValidServer;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Pterodactyl\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Pterodactyl\Http\Middleware\Daemon\DaemonAuthenticate;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Pterodactyl\Http\Middleware\Server\AuthenticateAsSubuser;
use Pterodactyl\Http\Middleware\Server\SubuserBelongsToServer;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Http\Middleware\Server\DatabaseBelongsToServer;
use Pterodactyl\Http\Middleware\Server\ScheduleBelongsToServer;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Pterodactyl\Http\Middleware\DaemonAuthenticate as OldDaemonAuthenticate;

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
        AddQueuedCookiesToResponse::class,
        TrimStrings::class,

        /*
         * Custom middleware applied to all routes.
         */
        TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            LanguageMiddleware::class,
            RequireTwoFactorAuthentication::class,
        ],
        'api' => [
            HMACAuthorization::class,
            'throttle:60,1',
            'bindings',
        ],
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
        'server' => AccessingValidServer::class,
        'subuser.auth' => AuthenticateAsSubuser::class,
        'admin' => AdminAuthenticate::class,
        'daemon-old' => OldDaemonAuthenticate::class,
        'csrf' => VerifyCsrfToken::class,
        'throttle' => ThrottleRequests::class,
        'can' => Authorize::class,
        'bindings' => SubstituteBindings::class,
        'recaptcha' => VerifyReCaptcha::class,

        // Server specific middleware (used for authenticating access to resources)
        //
        // These are only used for individual server authentication, and not gloabl
        // actions from other resources. They are defined in the route files.
        'server..database' => DatabaseBelongsToServer::class,
        'server..subuser' => SubuserBelongsToServer::class,
        'server..schedule' => ScheduleBelongsToServer::class,
    ];
}
