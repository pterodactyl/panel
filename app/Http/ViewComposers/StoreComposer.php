<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class StoreComposer
{
    private SettingsRepositoryInterface $settings;

    /**
     * StoreComposer constructor.
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view)
    {
        $prefix = 'jexactyl::store:';

        $view->with('storeConfiguration', [
            'enabled' => $this->settings->get($prefix.'enabled') ?? false,
            'paypal' => [
                'enabled' => $this->settings->get($prefix.'paypal:enabled') ?? false,
            ],
            'stripe' => [
                'enabled' => $this->settings->get($prefix.'stripe:enabled') ?? false,
            ],
            'cost' => [
                'cpu' => $this->settings->get($prefix.'cost:cpu'),
                'memory' => $this->settings->get($prefix.'cost:memory'),
                'disk' => $this->settings->get($prefix.'cost:disk'),
                'slot' => $this->settings->get($prefix.'cost:slot'),
                'port' => $this->settings->get($prefix.'cost:port'),
                'backup' => $this->settings->get($prefix.'cost:backup'),
                'database' => $this->settings->get($prefix.'cost:database'),
            ],
        ]);
    }
}
