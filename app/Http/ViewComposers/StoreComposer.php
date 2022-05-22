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
        $view->with('storeConfiguration', [
            'enabled' => $this->settings->get('jexactyl::store:enabled') ?? false,
        ]);
    }
}
