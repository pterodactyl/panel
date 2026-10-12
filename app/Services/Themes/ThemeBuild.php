<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

use Illuminate\Support\Facades\File;
use Pterodactyl\Services\Extensions\ExtensionManifest;

/**
 * The `dist/theme.json` defineThemeConfig writes beside a theme's build.
 */
final readonly class ThemeBuild
{
    public const int STYLESHEET_BUDGET = 250 * 1024;

    public const int ENTRY_BUDGET = 200 * 1024;

    /**
     * @param  string|null  $core  hash of the core class manifest the stylesheet was built against
     * @param  list<string>|null  $views  the view names the theme overrides, when the preset could read them
     */
    public function __construct(public ?string $core = null, public ?string $sdk = null, public ?array $views = null) {}

    public static function of(ExtensionManifest $theme): self
    {
        $path = $theme->path(...explode('/', ThemeDefinition::BUILD_INFO));
        $data = is_file($path) ? rescue(fn (): mixed => json_decode(File::get($path), true, 8, JSON_THROW_ON_ERROR), report: false) : null;
        if (! is_array($data)) {
            return new self;
        }

        return new self(
            core: is_string($data['core'] ?? null) ? $data['core'] : null,
            sdk: is_string($data['sdk'] ?? null) ? $data['sdk'] : null,
            views: is_array($data['views'] ?? null) ? array_values(array_filter($data['views'], is_string(...))) : null,
        );
    }
}
