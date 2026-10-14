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
    /**
     * @param  list<string>  $parents  the themes this one extends whose stylesheets its stylesheet was built over
     */
    public function __construct(
        public array $parents = [],
    ) {}

    public static function of(ExtensionManifest $theme): self
    {
        $path = $theme->path(...explode('/', ThemeDefinition::BUILD_INFO));
        $data = is_file($path) ? rescue(fn (): mixed => json_decode(File::get($path), true, 8, JSON_THROW_ON_ERROR), report: false) : null;
        if (! is_array($data)) {
            return new self;
        }

        $parents = $data['parents'] ?? null;

        return new self(
            parents: is_array($parents) ? array_values(array_filter($parents, is_string(...))) : [],
        );
    }
}
