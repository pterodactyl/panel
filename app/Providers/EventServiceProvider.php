<?php

namespace Pterodactyl\Providers;

use Pterodactyl\Traits\Helpers\OAuth2Providers;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Pterodactyl\Events\Server\Installed as ServerInstalledEvent;
use Pterodactyl\Notifications\ServerInstalled as ServerInstalledNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    use OAuth2Providers;
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ServerInstalledEvent::class => [
            ServerInstalledNotification::class,
        ],
        SocialiteWasCalled::class => [],
    ];

    /**
     * EventServiceProvider constructor.
     * @param $app
     */
    public function __construct($app)
    {
        parent::__construct($app);

        // Add OAuth2 provider listeners
        $this->listen[SocialiteWasCalled::class] = array_merge($this->listen[SocialiteWasCalled::class], array_filter($this->getProviderListeners()));
    }
}
