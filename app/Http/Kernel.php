<?php

namespace Pterodactyl\Http;

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\Authenticate;
use Pterodactyl\Http\Middleware\TrimStrings;
use Pterodactyl\Http\Middleware\TrustProxies;
use Illuminate\Session\Middleware\StartSession;
use Pterodactyl\Http\Middleware\EncryptCookies;
use Pterodactyl\Http\Middleware\Api\IsValidJson;
use Pterodactyl\Http\Middleware\VerifyCsrfToken;
use Pterodactyl\Http\Middleware\VerifyReCaptcha;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Pterodactyl\Http\Middleware\MaintenanceMiddleware;
use Pterodactyl\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Pterodactyl\Http\Middleware\Api\PreventUnboundModels;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Pterodactyl\Http\Middleware\Api\Daemon\DaemonAuthenticate;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Pterodactyl\Http\Middleware\Api\Client\SubstituteClientApiBindings;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Pterodactyl\Http\Middleware\Api\Application\AuthenticateApplicationUser;
use Pterodactyl\Http\Middleware\Api\Application\SubstituteApplicationApiBindings;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        TrustProxies::class,
        PreventRequestsDuringMaintenance::class,
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
            RequireTwoFactorAuthentication::class,
        ],
        'api' => [
            IsValidJson::class,
            EnsureFrontendRequestsAreStateful::class,
            'auth:sanctum',
            SubstituteApplicationApiBindings::class,
            PreventUnboundModels::class,
            AuthenticateApplicationUser::class,
            RequireTwoFactorAuthentication::class,
        ],
        'client-api' => [
            IsValidJson::class,
            EnsureFrontendRequestsAreStateful::class,
            'auth:sanctum',
            SubstituteClientApiBindings::class,
            PreventUnboundModels::class,
            // This is perhaps a little backwards with the Client API, but logically you'd be unable
            // to create/get an API key without first enabling 2FA on the account, so I suppose in the
            // end it makes sense.
            //
            // You just wouldn't be authenticating with the API by providing a 2FA token.
            RequireTwoFactorAuthentication::class,
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
        'admin' => AdminAuthenticate::class,
        'csrf' => VerifyCsrfToken::class,
        'throttle' => ThrottleRequests::class,
        'can' => Authorize::class,
        'bindings' => SubstituteBindings::class,
        'recaptcha' => VerifyReCaptcha::class,
        'node.maintenance' => MaintenanceMiddleware::class,
    ];
}
