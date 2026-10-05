<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Pterodactyl\Events\Server\Installed as ServerInstalledEvent;
use Pterodactyl\Listeners\AuthenticationListener;
use Pterodactyl\Listeners\RevocationListener;
use Pterodactyl\Listeners\TwoFactorListener;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Notifications\ServerInstalled as ServerInstalledNotification;
use Pterodactyl\Observers\ServerObserver;
use Pterodactyl\Observers\SubuserObserver;
use Pterodactyl\Observers\UserObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        ServerInstalledEvent::class => [ServerInstalledNotification::class],
    ];

    /**
     * The event subscribers to register.
     *
     * @var list<class-string>
     */
    protected $subscribe = [
        AuthenticationListener::class,
        RevocationListener::class,
        TwoFactorListener::class,
    ];

    protected static $shouldDiscoverEvents = false;

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        User::observe(UserObserver::class);
        Server::observe(ServerObserver::class);
        Subuser::observe(SubuserObserver::class);
    }
}
