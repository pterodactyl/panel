<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Services\Helpers\AssetHashService;

class AssetComposer
{
    /**
     * @var \Pterodactyl\Services\Helpers\AssetHashService
     */
    private $assetHashService;

    /**
     * AssetComposer constructor.
     *
     * @param \Pterodactyl\Services\Helpers\AssetHashService $assetHashService
     */
    public function __construct(AssetHashService $assetHashService)
    {
        $this->assetHashService = $assetHashService;
    }

    /**
     * Provide access to the asset service in the views.
     *
     * @param \Illuminate\View\View $view
     */
    public function compose(View $view)
    {
        $view->with('asset', $this->assetHashService);
    }
}
