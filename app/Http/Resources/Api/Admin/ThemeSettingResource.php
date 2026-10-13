<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Services\Themes\ThemeSetting;

/**
 * One entry of a theme's settings form.
 *
 * @extends AdminResource<array{setting: ThemeSetting, value: string|int|float|bool|null}>
 */
class ThemeSettingResource extends AdminResource
{
    public function resourceName(): string
    {
        return 'theme_setting';
    }

    public function toArray(Request $request): array
    {
        $setting = $this->resource['setting'];
        $image = $setting->type === 'image';

        return [
            'key' => $setting->key,
            'type' => $setting->type,
            'label' => $setting->label,
            'help' => $setting->help,
            'tab' => $setting->tab,
            'default' => $setting->default,
            'value' => $this->resource['value'],
            'options' => array_map(fn (string $value, string $label): array => ['value' => $value, 'label' => $label], array_keys($setting->options), $setting->options),
            'min' => $setting->min,
            'max' => $setting->max,
            'step' => $setting->step,
            'unit' => $setting->unit,
            'max_length' => $setting->type === 'text' ? $setting->maxLength : null,
            'accept' => $image ? ThemeSetting::IMAGE_TYPES : [],
            'max_kilobytes' => $image ? ExtensionSettingFiles::MAX_KILOBYTES : null,
        ];
    }
}
