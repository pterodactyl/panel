<?php

declare(strict_types=1);

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ExtensionHeadTags;
use Pterodactyl\Services\Extensions\ExtensionManager;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Support\JsonEmptyObject;
use Throwable;

class AssetComposer
{
    /**
     * AssetComposer constructor.
     */
    public function __construct(
        private readonly AssetHashService $assetHashService,
        private readonly ExtensionManager $extensionManager,
        private readonly ExtensionHeadTags $extensionHeadTags,
        private readonly ExtensionFields $extensionFields,
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
            'locale' => config('app.locale', 'en'),
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key', ''),
            ],
            'extensions' => $this->extensionPayload(),
            'extensionForms' => $this->extensionForms(),
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
            return $this->extensionManager->frontendPayload(authenticated: Auth::check());
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * The extensions whose fields each admin form shows, for root administrators only.
     *
     * @return array<string, list<array{id: string, name: string}>>|JsonEmptyObject
     */
    private function extensionForms(): array|JsonEmptyObject
    {
        $user = Auth::user();
        if (! config('extensions.enabled') || ! $user instanceof User || ! $user->root_admin) {
            return new JsonEmptyObject;
        }

        try {
            return $this->extensionFields->forms() ?: new JsonEmptyObject;
        } catch (Throwable) {
            return new JsonEmptyObject;
        }
    }
}
