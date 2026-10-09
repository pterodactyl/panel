<?php

declare(strict_types=1);

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Pterodactyl\Services\Extensions\ExtensionHeadTags;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Services\Helpers\BrandingLogoService;
use Throwable;

class AssetComposer
{
    /**
     * AssetComposer constructor.
     */
    public function __construct(
        private readonly AssetHashService $assetHashService,
        private readonly ExtensionRepository $extensions,
        private readonly ExtensionHeadTags $extensionHeadTags,
        private readonly BrandingLogoService $brandingLogo,
    ) {}

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        $view->with('asset', $this->assetHashService);
        // Rendered lazily by the layout; resolving the tags never throws.
        $view->with('extensionHead', $this->extensionHeadTags);
        $view->with('siteConfiguration', [
            'name' => config('app.name', 'Pterodactyl'),
            'logo' => $this->brandingLogo->url(),
            'locale' => config('app.locale', 'en'),
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key', ''),
            ],
            'extensions' => $this->extensionPayload(),
        ]);
    }

    /**
     * The enabled frontend extensions the SPA boot loader should import, with their
     * frontend settings for signed-in users and only the public ones for guests.
     * Never allowed to break page rendering - worst case the SPA sees no extensions.
     *
     * @return array<int, array{id: string, version: string, entry: string}>
     */
    private function extensionPayload(): array
    {
        if (! config('extensions.enabled')) {
            return [];
        }

        try {
            return $this->extensions->frontendPayload(authenticated: Auth::check());
        } catch (Throwable) {
            return [];
        }
    }
}
