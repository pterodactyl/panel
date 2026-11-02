<?php

declare(strict_types=1);

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Pterodactyl\Services\Extensions\ExtensionHeadTags;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Services\Helpers\BrandingLogoService;
use Pterodactyl\Services\Themes\ThemeResolver;
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
        private readonly ThemeResolver $themes,
        private readonly Request $request,
    ) {}

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        $theme = $this->themes->document($this->request);

        $view->with('asset', $this->assetHashService);
        // Rendered lazily by the layout; resolving the tags never throws.
        $view->with('extensionHead', $this->extensionHeadTags);
        $view->with('theme', $theme);
        $view->with('siteConfiguration', [
            'name' => config('app.name', 'Pterodactyl'),
            'logo' => $this->brandingLogo->url(),
            'locale' => config('app.locale', 'en'),
            'userAvatarsEnabled' => config('pterodactyl.client_features.avatars.enabled', false),
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key', ''),
            ],
            // Safe mode loads no extension bundles at all.
            'extensions' => $theme->safeMode ? [] : $this->extensionPayload(array_column($theme->chain, 'id')),
            'theme' => $theme->bootstrap(),
        ]);
    }

    /**
     * The enabled frontend extensions the SPA boot loader should import, with their
     * frontend settings for signed-in users and only the public ones for guests, and
     * the themes of the chain the page renders with. Never allowed to break page
     * rendering - worst case the SPA sees no extensions.
     *
     * @param  list<string>  $themes
     * @return array<int, array{id: string, version?: string, entry: string}>
     */
    private function extensionPayload(array $themes): array
    {
        if (! config('extensions.enabled')) {
            return [];
        }

        try {
            return $this->extensions->frontendPayload(authenticated: Auth::check(), themes: $themes);
        } catch (Throwable) {
            return [];
        }
    }
}
