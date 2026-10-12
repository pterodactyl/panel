<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettings;
use WeakMap;

/**
 * Decides which theme a request renders with: classic in safe mode, the previewed theme for
 * a root admin who asked for one, otherwise the active theme. A theme that cannot be used
 * renders as classic.
 */
class ThemeResolver
{
    /** Session flag set by `?safe_mode=1`: classic, without extension bundles. */
    public const string SAFE_MODE = 'theme.safe_mode';

    /** Session key holding the theme a root admin previews. */
    public const string PREVIEW = 'theme.preview';

    /** `dark`, `light` or `system`, written by the SPA. */
    public const string SCHEME_COOKIE = 'pterodactyl_scheme';

    /** @var WeakMap<Request, ThemeDocument> every view of a request renders the same theme */
    private WeakMap $documents;

    public function __construct(
        private readonly ThemeRepository $themes,
        private readonly ExtensionRepository $extensions,
        private readonly ExtensionAssetPublisher $assets,
    ) {
        $this->documents = new WeakMap;
    }

    /** Never throws: whatever breaks renders the classic theme. */
    public function document(Request $request): ThemeDocument
    {
        return $this->documents[$request] ??= rescue(fn (): ThemeDocument => $this->resolve($request), fn (): ThemeDocument => new ThemeDocument);
    }

    private function resolve(Request $request): ThemeDocument
    {
        $session = $request->hasSession() ? $request->session() : null;
        $safeMode = $session?->get(self::SAFE_MODE) === true;
        $admin = $request->user()?->root_admin === true;
        $preview = $admin && ! $safeMode ? $session?->get(self::PREVIEW) : null;

        [$id, $chain] = $safeMode ? [ThemeRepository::CLASSIC, []] : $this->usable(array_filter([is_string($preview) ? $preview : null, $this->themes->active()]));
        $schemes = $this->themes->schemes($chain);
        $preference = $request->cookie(self::SCHEME_COOKIE);
        $styled = array_find(array_reverse($chain), fn (ExtensionManifest $theme): bool => $theme->theme?->stylesheet !== null);
        $stylesheet = $styled?->theme?->stylesheet;
        [$settings, $tokens, $attributes] = $this->settings($chain);

        return new ThemeDocument(
            id: $id,
            chain: array_map(fn (ExtensionManifest $theme): array => [
                'id' => $theme->id,
                'version' => $theme->version,
                'entry' => $theme->hasUi() ? $this->assets->entryUrl($theme) : null,
                'development' => $this->assets->developmentPayload($theme->id),
            ], $chain),
            settings: $settings,
            tokens: $tokens,
            attributes: $attributes,
            scheme: in_array($preference, $schemes, true) ? $preference : $schemes[0],
            schemes: $schemes,
            systemScheme: $preference === 'system' && count($schemes) > 1,
            safeMode: $safeMode,
            preview: $preview !== null && $id === $preview,
            stylesheet: $styled === null || $stylesheet === null ? null : $this->assets->assetUrl($styled, Str::after($stylesheet, 'dist/')),
            // Built against other core classes, the theme stylesheet is laid over the panel's own.
            stylesheetFallback: $styled !== null && $this->themes->coreMismatch($styled) !== null,
        );
    }

    /**
     * The first of the candidates whose chain can be used, else classic.
     *
     * @param  array<int, string>  $candidates
     * @return array{0: string, 1: list<ExtensionManifest>}
     */
    private function usable(array $candidates): array
    {
        foreach ($candidates as $id) {
            try {
                return [$id, $this->themes->chain($id)];
            } catch (InvalidExtensionException) {
                continue;
            }
        }

        return [ThemeRepository::CLASSIC, []];
    }

    /**
     * The chain's setting values, and what they write as tokens and attributes. A child's
     * setting of the same name replaces its parent's, and its value is the child's.
     *
     * @param  list<ExtensionManifest>  $chain
     * @return array{0: array<string, string|int|float|bool|null>, 1: array<string, string>, 2: array<string, string>}
     */
    private function settings(array $chain): array
    {
        ExtensionSettings::preload(array_map(fn (ExtensionManifest $theme): ExtensionSettings => $this->extensions->settings($theme->id), $chain));
        $values = [];
        $tokens = [];
        $attributes = [];
        foreach ($chain as $theme) {
            $stored = $this->themes->settings($theme);
            foreach ($this->themes->declared($theme) as $key => $setting) {
                $value = $setting->value(rescue(fn (): mixed => $stored->get(ThemeSetting::KEY_PREFIX.$key), report: false));
                $values[$key] = $value;
                if ($setting->token !== null && ($token = $setting->tokenValue($value)) !== null) {
                    $tokens[$setting->token] = $token;
                }

                if ($setting->attribute !== null && ($attribute = $setting->attributeValue($value)) !== null) {
                    $attributes[$setting->attribute] = $attribute;
                }
            }
        }

        return [$values, $tokens, $attributes];
    }
}
