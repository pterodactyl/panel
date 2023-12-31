<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class AssetComposer
{
    public function __construct(private SettingsRepositoryInterface $repository)
    {
    }
    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        $view->with('siteConfiguration', [
            'name' => $this->repository->get('appName', config('app.name')),
            'locale' => $this->repository->get('language', config('app.locale')),
            'recaptcha' => [
                'enabled' => (bool)$this->repository->get('recaptchaEnabled', config('recaptcha.enabled', false)),
                'siteKey' => $this->repository->get('recaptchaSiteKey',config('recaptcha.website_key') ?? ''),
            ],
        ]);
    }
}
