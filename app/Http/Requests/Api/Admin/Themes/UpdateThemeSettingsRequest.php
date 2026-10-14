<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Themes;

use Illuminate\Support\Facades\Validator;
use Pterodactyl\Rules\ValidExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;

class UpdateThemeSettingsRequest extends UpdateThemeRequest
{
    /** @return ValidationRules */
    public function rules(): array
    {
        return ['settings' => ['bail', 'present', 'array', new ValidExtensionSettings]];
    }

    /**
     * The submitted values, checked against each setting's type and limits.
     *
     * @return array<string, mixed>
     */
    public function settings(ExtensionSettingsDefinition $definition): array
    {
        $input = $this->validated('settings');
        ExtensionSettingValueGuard::assertValues($input);

        $settings = Validator::make($input, $definition->validationRules())->validate();
        ExtensionSettingValueGuard::assertValues($settings);

        return $settings;
    }
}
