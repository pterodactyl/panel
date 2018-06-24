<?php

namespace Pterodactyl\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Pterodactyl\Events\Server\Installed;
use Pterodactyl\Listeners\Server\SendInstalledEmail;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Installed::class => [SendInstalledEmail::class],
    ];
}
