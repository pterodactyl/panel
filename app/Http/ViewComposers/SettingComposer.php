<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Http\ViewComposers\Composer;
use Pterodactyl\Services\Helpers\AssetHashService;

class SettingComposer extends Composer
{
    private AssetHashService $assetHashService;

    /**
     * AssetComposer constructor.
     */
    public function __construct(AssetHashService $assetHashService)
    {
        parent::__construct();
        $this->assetHashService = $assetHashService;
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view)
    {
        $view->with('asset', $this->assetHashService);

        $view->with('siteConfiguration', [
            'name' => config('app.name') ?? 'Pterodactyl',
            'locale' => config('app.locale') ?? 'en',
            'logo' => $this->setting('logo', Composer::TYPE_STR),
    
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],

            'registration' => [
                'email' => $this->setting('registration:enabled', Composer::TYPE_BOOL),
                'discord' => $this->setting('discord:enabled', Composer::TYPE_BOOL),
            ],

            'approvals' => $this->setting('approvals:enabled', Composer::TYPE_BOOL),
        ]);
    }
}
