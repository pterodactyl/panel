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
     * @param  string|null  $build  hash of dist/theme-source.css and dist/classes.txt, which the build of a theme extending this one imports
     * @param  array<string, string|null>|null  $parents  the builds of the themes this one extends that its stylesheet was built over, by id
     */
    public function __construct(
        public ?string $core = null,
        public ?string $sdk = null,
        public ?array $views = null,
        public ?string $build = null,
        public ?array $parents = null,
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
            core: is_string($data['core'] ?? null) ? $data['core'] : null,
            sdk: is_string($data['sdk'] ?? null) ? $data['sdk'] : null,
            views: is_array($data['views'] ?? null) ? array_values(array_filter($data['views'], is_string(...))) : null,
            build: is_string($data['build'] ?? null) ? $data['build'] : null,
            parents: is_array($parents) ? array_filter($parents, fn (mixed $build, int|string $id): bool => is_string($id) && ($build === null || is_string($build)), ARRAY_FILTER_USE_BOTH) : null,
        );
    }
}
