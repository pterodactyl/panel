<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Http\ViewComposers\Composer;

class StoreComposer extends Composer
{
    /**
     * StoreComposer constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view)
    {
        $view->with('storeConfiguration', [
            'enabled' => $this->setting('store:enabled', Composer::TYPE_BOOL),
            'paypal' => $this->setting('store:paypal:enabled', Composer::TYPE_BOOL),
            'stripe' => $this->setting('store:stripe:enabled', Composer::TYPE_BOOL),
            'currency' => $this->setting('store:currency', Composer::TYPE_STR),

            'renewals' => [
                'enabled' => $this->setting('renewal:enabled', Composer::TYPE_BOOL),
                'cost' => $this->setting('renewal:cost', Composer::TYPE_INT),
                'days' => $this->setting('renewal:default', Composer::TYPE_INT),
            ],

            'editing' => [
                'enabled' => $this->setting('renewal:editing', Composer::TYPE_BOOL),
            ],

            'referrals' => [
                'enabled' => $this->setting('referrals:enabled', Composer::TYPE_BOOL),
                'reward' => $this->setting('referrals:reward', Composer::TYPE_INT),
            ],

            'earn' => [
                'enabled' => $this->setting('earn:enabled', Composer::TYPE_BOOL),
                'amount' => $this->setting('earn:amount', Composer::TYPE_INT),
            ],

            'cost' => [
                'cpu' => $this->setting('store:cost:cpu', Composer::TYPE_INT),
                'memory' => $this->setting('store:cost:memory', Composer::TYPE_INT),
                'disk' => $this->setting('store:cost:disk', Composer::TYPE_INT),
                'slot' => $this->setting('store:cost:slot', Composer::TYPE_INT),
                'port' => $this->setting('store:cost:port', Composer::TYPE_INT),
                'backup' => $this->setting('store:cost:backup', Composer::TYPE_INT),
                'database' => $this->setting('store:cost:database', Composer::TYPE_INT),
            ],

            'limit' => [
                'cpu' => $this->setting('store:limit:cpu', Composer::TYPE_INT),
                'memory' => $this->setting('store:limit:memory', Composer::TYPE_INT),
                'disk' => $this->setting('store:limit:disk', Composer::TYPE_INT),
                'port' => $this->setting('store:limit:port', Composer::TYPE_INT),
                'backup' => $this->setting('store:limit:backup', Composer::TYPE_INT),
                'database' => $this->setting('store:limit:database', Composer::TYPE_INT),
            ]
        ]);
    }
}
