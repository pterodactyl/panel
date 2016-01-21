<?php

namespace Pterodactyl\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

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
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Pterodactyl\Http\Middleware\LanguageMiddleware::class,
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Pterodactyl\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest' => \Pterodactyl\Http\Middleware\RedirectIfAuthenticated::class,
        'server' => \Pterodactyl\Http\Middleware\CheckServer::class,
        'admin' => \Pterodactyl\Http\Middleware\AdminAuthenticate::class,
        'csrf' => \Pterodactyl\Http\Middleware\VerifyCsrfToken::class,
    ];
}
