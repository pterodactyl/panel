<?php

namespace App\Http;

use App\Models\ApiKey;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\VerifyReCaptcha;
use Illuminate\Auth\Middleware\Authorize;
use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\LanguageMiddleware;
use App\Http\Middleware\Api\AuthenticateKey;
use Illuminate\Auth\Middleware\Authenticate;
use App\Http\Middleware\Api\SetSessionDriver;
use App\Http\Middleware\MaintenanceMiddleware;
use Illuminate\Session\Middleware\StartSession;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\Api\AuthenticateIPAccess;
use App\Http\Middleware\Api\ApiSubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use App\Http\Middleware\Server\AccessingValidServer;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use App\Http\Middleware\Server\AuthenticateAsSubuser;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Http\Middleware\Api\Daemon\DaemonAuthenticate;
use App\Http\Middleware\Server\SubuserBelongsToServer;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\RequireTwoFactorAuthentication;
use App\Http\Middleware\Server\DatabaseBelongsToServer;
use App\Http\Middleware\Server\ScheduleBelongsToServer;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Http\Middleware\Api\Client\SubstituteClientApiBindings;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use App\Http\Middleware\Api\Application\AuthenticateApplicationUser;
use App\Http\Middleware\DaemonAuthenticate as OldDaemonAuthenticate;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        TrustProxies::class,
        CheckForMaintenanceMode::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
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
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            LanguageMiddleware::class,
            RequireTwoFactorAuthentication::class,
        ],
        'api' => [
            'throttle:120,1',
            ApiSubstituteBindings::class,
            SetSessionDriver::class,
            'api..key:' . ApiKey::TYPE_APPLICATION,
            AuthenticateApplicationUser::class,
            AuthenticateIPAccess::class,
        ],
        'client-api' => [
            'throttle:60,1',
            SubstituteClientApiBindings::class,
            SetSessionDriver::class,
            'api..key:' . ApiKey::TYPE_ACCOUNT,
            AuthenticateIPAccess::class,
        ],
        'daemon' => [
            SubstituteBindings::class,
            DaemonAuthenticate::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'server' => AccessingValidServer::class,
        'subuser.auth' => AuthenticateAsSubuser::class,
        'admin' => AdminAuthenticate::class,
        'daemon-old' => OldDaemonAuthenticate::class,
        'csrf' => VerifyCsrfToken::class,
    'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'can' => Authorize::class,
        'bindings' => SubstituteBindings::class,
        'recaptcha' => VerifyReCaptcha::class,
    'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'node.maintenance' => MaintenanceMiddleware::class,

        // Server specific middleware (used for authenticating access to resources)
        //
        // These are only used for individual server authentication, and not global
        // actions from other resources. They are defined in the route files.
        'server..database' => DatabaseBelongsToServer::class,
        'server..subuser' => SubuserBelongsToServer::class,
        'server..schedule' => ScheduleBelongsToServer::class,

        // API Specific Middleware
        'api..key' => AuthenticateKey::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\Authenticate::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
