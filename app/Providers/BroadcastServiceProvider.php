<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Models\User;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes();

        /*
         * Authenticate the user's personal channel...
         */
        Broadcast::channel('App.User.*', function (User $user, string $userId): bool {
            $id = filter_var($userId, FILTER_VALIDATE_INT);

            return $id !== false && $user->id === $id;
        });
    }
}
