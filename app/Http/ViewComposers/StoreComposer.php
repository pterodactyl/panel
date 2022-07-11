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
            'currency' => $this->getSetting('currency', 'JCR'),

            'referrals' => [
                'enabled' => $this->settings->get('jexactyl::referrals:enabled', false),
            ],

            'earn' => [
                'enabled' => $this->settings->get('jexactyl::earn:enabled', false),
                'amount' => $this->settings->get('jexactyl::earn:amount', 1),
            ],

            'gateways' => [
                'paypal' => $this->getSetting('paypal:enabled', false),
                'stripe' => $this->getSetting('stripe:enabled', false),
            ],

            'cost' => [
                'cpu' => $this->getSetting('cost:cpu', 100),
                'memory' => $this->getSetting('cost:memory', 50),
                'disk' => $this->getSetting('cost:disk', 25),
                'slot' => $this->getSetting('cost:slot', 250),
                'port' => $this->getSetting('cost:port', 20),
                'backup' => $this->getSetting('cost:backup', 20),
                'database' => $this->getSetting('cost:database', 20),
            ],

            'limit' => [
                'cpu' => $this->getSetting('limit:cpu', 100),
                'memory' => $this->getSetting('limit:memory', 4096),
                'disk' => $this->getSetting('limit:disk', 10240),
                'port' => $this->getSetting('limit:port', 1),
                'backup' => $this->getSetting('limit:backup', 1),
                'database' => $this->getSetting('limit:database', 1),
            ]
        ]);
    }
}
