<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

use Illuminate\Support\Collection;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Models\Setting;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Helpers\AssetHashService;

/**
 * Installed themes, the active one, and the chain of parents each is built on.
 */
class ThemeRepository
{
    /** The built-in theme every chain ends in. */
    public const string CLASSIC = 'classic';

    /** The `settings` row naming the active theme; without it classic is active. */
    public const string SETTING = 'pterodactyl:theme';

    private ?string $active = null;

    public function __construct(private readonly ExtensionRepository $extensions, private readonly AssetHashService $assets) {}

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

        return array_reverse(array_values($chain));
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
     * The schemes of the nearest theme in the chain that declares them.
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

        return ThemeDefinition::SCHEMES;
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

    public function settings(ExtensionManifest $theme): ExtensionSettingsDefinition
    {
        return ($theme->theme ?? new ThemeDefinition)->settingsDefinition($this->extensions->settings($theme->id));
    }
}
