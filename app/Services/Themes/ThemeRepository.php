<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

use Illuminate\Support\Collection;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Models\Setting;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Helpers\AssetHashService;
use WeakMap;

/**
 * Installed themes, the active one, and the chain of parents each is built on.
 */
class ThemeRepository
{
    /** The built-in theme every chain ends in. */
    public const string CLASSIC = 'classic';

    /** Classic's light tokens are unaudited, so it only offers its dark scheme. */
    public const array CLASSIC_SCHEMES = ['dark'];

    /** The `settings` row naming the active theme; without it classic is active. */
    public const string SETTING = 'pterodactyl:theme';

    private ?string $active = null;

    /** @var WeakMap<ExtensionManifest, array<string, ThemeSetting>> */
    private WeakMap $declarations;

    public function __construct(
        private readonly ExtensionRepository $extensions,
        private readonly AssetHashService $assets,
        private readonly ExtensionManifestValidator $validator,
    ) {
        $this->declarations = new WeakMap;
    }

    /** @return Collection<string, ExtensionManifest> */
    public function installed(): Collection
    {
        return $this->extensions->discovered()->filter(fn (ExtensionManifest $manifest): bool => $manifest->isTheme());
    }

    public function active(): string
    {
        return $this->active ??= rescue(fn (): ?string => Setting::fetch(self::SETTING), report: false) ?: self::CLASSIC;
    }

    public function setActive(string $id): void
    {
        if ($id === self::CLASSIC) {
            Setting::query()->where('key', self::SETTING)->delete();
        } else {
            Setting::put(self::SETTING, $id);
        }

        $this->active = $id;
    }

    /**
     * The enabled themes a theme is built from, parent first and without classic. A missing,
     * disabled or circular parent makes the theme unusable.
     *
     * @return list<ExtensionManifest>
     *
     * @throws InvalidExtensionException
     */
    public function chain(string $id): array
    {
        $chain = [];
        $child = null;
        throw_if($id !== self::CLASSIC && ! config('extensions.enabled'), InvalidExtensionException::class, 'Extensions are turned off on this panel.');
        while ($id !== self::CLASSIC) {
            throw_if(isset($chain[$id]), InvalidExtensionException::class, sprintf('Theme "%s" extends itself through "%s".', $id, $child));

            $manifest = $this->extensions->discovered()->get($id);
            throw_unless($manifest?->isTheme(), InvalidExtensionException::class, $child === null
                ? sprintf('"%s" is not an installed theme.', $id)
                : sprintf('Theme "%s" extends "%s", which is not an installed theme.', $child, $id));
            throw_unless($this->extensions->enabled()->has($id), InvalidExtensionException::class, sprintf('Theme "%s" is not enabled or failed to load.', $id));

            $chain[$id] = $manifest;
            $child = $id;
            $id = $manifest->theme->extends;
        }

        $chain = array_reverse(array_values($chain));
        foreach ($chain as $theme) {
            $this->declarations($theme);
        }

        return $chain;
    }

    /**
     * The themes a theme extends, oldest first and without classic, found as its build finds them:
     * installed in the panel, else in the package's node_modules. The list starts after a parent
     * that is in neither.
     *
     * @return list<ExtensionManifest>
     */
    public function ancestors(ExtensionManifest $theme): array
    {
        $ancestors = [];
        $seen = [$theme->id => true];
        $id = $theme->theme->extends ?? self::CLASSIC;
        while ($id !== self::CLASSIC && ! isset($seen[$id])) {
            $seen[$id] = true;
            $parent = $this->extensions->discovered()->get($id);
            if (! $parent instanceof ExtensionManifest && is_dir($theme->path('node_modules', $id))) {
                try {
                    $parent = $this->validator->fromDirectory($theme->path('node_modules', $id));
                } catch (InvalidExtensionException) {
                }
            }

            if (! $parent?->isTheme() || $parent->id !== $id) {
                break;
            }

            array_unshift($ancestors, $parent);
            $id = $parent->theme->extends;
        }

        return $ancestors;
    }

    /**
     * The settings a theme declares: those it declares in full, and each override merged over the
     * declaration of the nearest theme it extends that has the setting.
     *
     * @return array<string, ThemeSetting>
     *
     * @throws InvalidExtensionException when an override changes no setting of those themes, or makes an invalid one
     */
    public function declarations(ExtensionManifest $theme): array
    {
        if (isset($this->declarations[$theme])) {
            return $this->declarations[$theme];
        }

        $ancestors = $this->ancestors($theme);
        $parent = $theme->theme->extends ?? self::CLASSIC;
        throw_if(($theme->theme->overrides ?? []) !== [] && (end($ancestors) ?: null)?->id !== $parent, InvalidExtensionException::class, sprintf('Theme "%s" overrides settings of "%s", which is not installed.', $theme->id, $parent));

        $inherited = [];
        foreach ($ancestors as $ancestor) {
            $inherited = [...$inherited, ...$this->declaredOver($ancestor, $inherited)];
        }

        return $this->declarations[$theme] = $this->declaredOver($theme, $inherited);
    }

    /**
     * Why a child theme's stylesheet cannot be used: dist/theme.json does not record the builds of the
     * themes it extends, so it was not built over their stylesheets and classes.
     */
    public function parentBuildProblem(ExtensionManifest $theme): ?string
    {
        if ($theme->theme?->stylesheet === null || $theme->theme->extends === self::CLASSIC) {
            return null;
        }

        $ancestors = array_map(fn (ExtensionManifest $ancestor): string => $ancestor->id, $this->ancestors($theme));
        $missing = array_values(array_diff(array_unique([...$ancestors, $theme->theme->extends]), array_keys(ThemeBuild::of($theme)->parents ?? [])));

        return $missing === [] ? null : sprintf(
            'Theme "%s" extends "%s", but its stylesheet was not built over the stylesheet of "%s". Rebuild it with this panel\'s SDK, whose build brings in the stylesheets of the themes it extends.',
            $theme->id,
            $theme->theme->extends,
            implode('", "', $missing),
        );
    }

    /**
     * The themes a child theme extends that were rebuilt since its stylesheet was built over them.
     *
     * @return list<string>
     */
    public function staleParents(ExtensionManifest $theme): array
    {
        $recorded = ThemeBuild::of($theme)->parents ?? [];
        $stale = [];
        foreach ($this->ancestors($theme) as $ancestor) {
            $current = ThemeBuild::of($ancestor)->build;
            if (($recorded[$ancestor->id] ?? null) !== null && $current !== null && $recorded[$ancestor->id] !== $current) {
                $stale[] = sprintf('Theme "%s" was built over build %s of "%s", which is now build %s; rebuild "%s" to bring in its current stylesheet.', $theme->id, $recorded[$ancestor->id], $ancestor->id, $current, $theme->id);
            }
        }

        return $stale;
    }

    /** @throws InvalidExtensionException while the theme is the active one */
    public function assertNotActive(string $id): void
    {
        throw_if($id === $this->active(), InvalidExtensionException::class, sprintf('Theme "%s" is active; activate another theme or reset to classic first.', $id));
    }

    /** Why the theme cannot be activated, or null when it can. */
    public function activationProblem(string $id): ?string
    {
        try {
            $this->chain($id);
        } catch (InvalidExtensionException $invalidExtensionException) {
            return $invalidExtensionException->getMessage();
        }

        return null;
    }

    /**
     * The schemes of the nearest theme in the chain that declares them, else classic's.
     *
     * @param  list<ExtensionManifest>  $chain
     * @return non-empty-list<'dark'|'light'>
     */
    public function schemes(array $chain): array
    {
        foreach (array_reverse($chain) as $manifest) {
            if ($manifest->theme?->schemes !== null && $manifest->theme->schemes !== []) {
                return $manifest->theme->schemes;
            }
        }

        return self::CLASSIC_SCHEMES;
    }

    /** Why the theme's stylesheet would be laid over the panel's instead of replacing it, or null. */
    public function coreMismatch(ExtensionManifest $theme): ?string
    {
        if ($theme->theme?->stylesheet === null) {
            return null;
        }

        $built = ThemeBuild::of($theme)->core;
        $panel = $this->assets->coreClassesHash();

        return $built !== null && $built === $panel ? null : sprintf(
            'Theme "%s" was built against core classes "%s", but this panel has "%s". Its stylesheet loads after the panel\'s until it is rebuilt with this panel\'s SDK.',
            $theme->id,
            $built ?? 'unknown',
            $panel ?? 'unknown',
        );
    }

    /**
     * The settings a theme declares, as far as they can be resolved: an override of a parent that is
     * not installed is left out.
     *
     * @return array<string, ThemeSetting>
     */
    public function declared(ExtensionManifest $theme): array
    {
        try {
            return $this->declarations($theme);
        } catch (InvalidExtensionException) {
            return $theme->theme->settings ?? [];
        }
    }

    public function settings(ExtensionManifest $theme): ExtensionSettingsDefinition
    {
        return new ExtensionSettingsDefinition($this->extensions->settings($theme->id), array_values(array_map(fn (ThemeSetting $setting): ExtensionSettingDefinition => $setting->definition(), $this->declared($theme))));
    }

    /**
     * @param  array<string, ThemeSetting>  $inherited  the settings of the themes it extends, by name
     * @return array<string, ThemeSetting>
     */
    private function declaredOver(ExtensionManifest $theme, array $inherited): array
    {
        $declared = [];
        foreach ($theme->theme->overrides ?? [] as $key => $override) {
            $parent = $inherited[$key] ?? throw new InvalidExtensionException(sprintf('Theme "%s" overrides setting "%s", which no theme it extends declares.', $theme->id, $key));
            try {
                $declared[$key] = $this->validator->themeSetting($key, $parent->overriddenBy($override));
            } catch (InvalidExtensionException $invalidExtensionException) {
                throw new InvalidExtensionException(sprintf('Theme "%s" overrides setting "%s": %s', $theme->id, $key, $invalidExtensionException->getMessage()), $invalidExtensionException->getCode(), previous: $invalidExtensionException);
            }
        }

        $declared = [...$declared, ...$theme->theme->settings ?? []];
        $targets = [];
        foreach ($declared as $key => $setting) {
            foreach (array_filter([$setting->token, $setting->attribute]) as $target) {
                throw_if(isset($targets[$target]), InvalidExtensionException::class, sprintf('Theme settings "%s" and "%s" of "%s" both write "%s".', $targets[$target] ?? '', $key, $theme->id, $target));
                $targets[$target] = $key;
            }
        }

        return $declared;
    }
}
