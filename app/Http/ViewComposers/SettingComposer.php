<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SettingComposer
{
    private AssetHashService $assetHashService;
    private SettingsRepositoryInterface $settings;

    /**
     * AssetComposer constructor.
     */
    public function __construct(
        AssetHashService $assetHashService,
        SettingsRepositoryInterface $settings,
    )
    {
        $this->assetHashService = $assetHashService;
        $this->settings = $settings;
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
            'logo' => $this->settings->get('settings::app:logo') ?? 'https://www.jexactyl.com/assets/images/logo.png',
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],
            'registration' => [
                'email' => $this->settings->get('jexactyl::registration:enabled', false),
                'discord' => $this->settings->get('jexactyl::discord:enabled', false),
            ],
            'renewal' => $this->settings->get('jexactyl::renewal:enabled', false),
        ]);
    }
}
