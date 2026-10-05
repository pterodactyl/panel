<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Extensions;

use Illuminate\Support\Facades\Validator;
use Pterodactyl\Rules\ValidExtensionSettings;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;

class UpdateExtensionSettingsRequest extends UpdateExtensionRequest
{
    /** @return ValidationRules */
    public function rules(): array
    {
        return ['settings' => ['bail', 'present', 'array', new ValidExtensionSettings]];
    }

    /** @return ExtensionSettingValues */
    public function settings(ExtensionSettingsDefinition $definition): array
    {
        $input = $this->validated('settings');
        ExtensionSettingValueGuard::assertValues($input);

        $settings = Validator::make(
            $definition->withoutBlankSecrets($input),
            $definition->validationRules(),
        )->validate();
        ExtensionSettingValueGuard::assertValues($settings);

        return $settings;
    }
}
