<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;

class StoreComposer
{
    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view)
    {
        $view->with('storeConfiguration', [
            'enabled' => true,
        ]);
    }
}
