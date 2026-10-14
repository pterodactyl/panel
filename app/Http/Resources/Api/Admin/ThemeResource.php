<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Themes\ThemeRepository;

/**
 * A theme as the admin Themes page lists it; a null manifest is the built-in classic theme.
 *
 * @extends AdminResource<array{theme: ExtensionManifest|null, icon_url: string|null, enabled: bool, active: bool, previewing: bool, problem: string|null}>
 */
class ThemeResource extends AdminResource
{
    public function resourceName(): string
    {
        return 'theme';
    }

    public function toArray(Request $request): array
    {
        $theme = $this->resource['theme'];

        return [
            'id' => $theme->id ?? ThemeRepository::CLASSIC,
            'name' => $theme->name ?? 'Classic',
            'version' => $theme?->version,
            'description' => $theme === null ? "The panel's own look, built into every installation." : $theme->description,
            'author' => $theme?->author,
            'icon' => $theme?->iconName(),
            'icon_url' => $this->resource['icon_url'],
            'builtin' => $theme === null,
            'enabled' => $this->resource['enabled'],
            'active' => $this->resource['active'],
            'previewing' => $this->resource['previewing'],
            'extends' => $theme?->theme?->extends,
            'settings_count' => count($theme?->theme->settings ?? []),
            'problem' => $this->resource['problem'],
        ];
    }
}
