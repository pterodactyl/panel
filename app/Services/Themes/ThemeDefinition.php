<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

/**
 * The `theme` section of a theme package's extension.json.
 */
final readonly class ThemeDefinition
{
    public const string STYLESHEET = 'dist/theme.css';

    /** Written by the SDK's defineThemeConfig: the core class manifest hash, SDK version and view names. */
    public const string BUILD_INFO = 'dist/theme.json';

    public const array SCHEMES = ['dark', 'light'];

    /**
     * @param  list<'dark'|'light'>|null  $schemes  null inherits the parent's
     * @param  array<string, ThemeSetting>  $settings  the settings declared in full
     * @param  array<string, ExtensionThemeSettingOverride>  $overrides  the fields that change a setting of the same name of a theme this one extends
     */
    public function __construct(
        public string $extends = ThemeRepository::CLASSIC,
        public ?string $stylesheet = null,
        public ?array $schemes = null,
        public array $settings = [],
        public array $overrides = [],
    ) {}

    /** @param ExtensionThemeInput $data */
    public static function fromValidatedData(array $data): self
    {
        $settings = [];
        $overrides = [];
        foreach ($data['settings'] ?? [] as $key => $setting) {
            if (isset($setting['type'], $setting['label'])) {
                $settings[$key] = ThemeSetting::fromValidatedData($key, $setting);
            } else {
                $overrides[$key] = $setting;
            }
        }

        return new self(
            extends: $data['extends'] ?? ThemeRepository::CLASSIC,
            stylesheet: $data['stylesheet'] ?? null,
            schemes: $data['schemes'] ?? null,
            settings: $settings,
            overrides: $overrides,
        );
    }
}
