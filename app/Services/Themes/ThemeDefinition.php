<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;

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
     * @param  array<string, ThemeSetting>  $settings
     */
    public function __construct(
        public string $extends = ThemeRepository::CLASSIC,
        public ?string $stylesheet = null,
        public ?array $schemes = null,
        public array $settings = [],
    ) {}

    /** @param ExtensionThemeInput $data */
    public static function fromValidatedData(array $data): self
    {
        $settings = [];
        foreach ($data['settings'] ?? [] as $key => $setting) {
            $settings[$key] = ThemeSetting::fromValidatedData($key, $setting);
        }

        return new self(
            extends: $data['extends'] ?? ThemeRepository::CLASSIC,
            stylesheet: $data['stylesheet'] ?? null,
            schemes: $data['schemes'] ?? null,
            settings: $settings,
        );
    }

    public function settingsDefinition(ExtensionSettings $settings): ExtensionSettingsDefinition
    {
        return new ExtensionSettingsDefinition($settings, array_values(array_map(fn (ThemeSetting $setting): ExtensionSettingDefinition => $setting->definition(), $this->settings)));
    }
}
