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
     * Retrieve the requested setting from the database.
     */
    protected function getSetting(string $data)
    {
        return $this->settings->get('jexactyl::store:'.$data);
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view)
    {
        $view->with('storeConfiguration', [
            'enabled' => $this->getSetting('enabled'),
            'gateways' => [
                'paypal' => $this->getSetting('paypal:enabled') ?? false,
                'stripe' => $this->getSetting('stripe:enabled') ?? false,
            ],
            'cost' => [
                'cpu' => $this->getSetting('cost:cpu'),
                'memory' => $this->getSetting('cost:memory'),
                'disk' => $this->getSetting('cost:disk'),
                'slot' => $this->getSetting('cost:slot'),
                'port' => $this->getSetting('cost:port'),
                'backup' => $this->getSetting('cost:backup'),
                'database' => $this->getSetting('cost:database'),
            ],
        ]);
    }
}
