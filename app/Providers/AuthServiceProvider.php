<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Policies\ServerPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        Server::class => ServerPolicy::class,
    ];

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(ApiKey::class);

        // Root administrators implicitly hold every ability, including all admin API permissions.
        Gate::before(fn (User $user): ?true => $user->root_admin ? true : null);
    }
}
