<?php

namespace Pterodactyl\Providers;

use Pterodactyl\Events\Server\Console;
use Pterodactyl\Events\Server\Power;
use Pterodactyl\Events\Server\Stats;
use Pterodactyl\Listeners\HandleServerConsoleEvent;
use Pterodactyl\Listeners\HandleServerPowerEvent;
use Pterodactyl\Listeners\HandleServerStatsEvent;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Observers\UserObserver;
use Pterodactyl\Observers\ServerObserver;
use Pterodactyl\Observers\SubuserObserver;
use Pterodactyl\Observers\EggVariableObserver;
use Pterodactyl\Listeners\Auth\AuthenticationListener;
use Pterodactyl\Events\Server\Installed as ServerInstalledEvent;
use Pterodactyl\Notifications\ServerInstalled as ServerInstalledNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        ServerInstalledEvent::class => [ServerInstalledNotification::class],
        Power::class => [HandleServerPowerEvent::class],
        Console::class => [HandleServerConsoleEvent::class],
        Stats::class => [HandleServerStatsEvent::class],
    ];

    protected $subscribe = [
        AuthenticationListener::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        User::observe(UserObserver::class);
        Server::observe(ServerObserver::class);
        Subuser::observe(SubuserObserver::class);
        EggVariable::observe(EggVariableObserver::class);
    }
}
