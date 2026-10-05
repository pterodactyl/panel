<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Settings;

use Illuminate\Validation\Rule;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Traits\Helpers\AvailableLanguages;

class UpdateGeneralSettingsRequest extends AdminApiRequest
{
    use AvailableLanguages;

    public function permissions(): array
    {
        return [Permissions::AdminSettingsUpdate];
    }

    /**
     * Validation rules for updating general settings.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'branding.name' => ['required', 'string', 'max:191'],
            'misc.required_2fa' => ['required', 'integer', 'in:0,1,2'],
            'default_locale' => ['required', 'string', Rule::in(array_keys($this->getAvailableLanguages()))],
        ];
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'branding.name' => 'Company Name',
            'misc.required_2fa' => 'Require 2-Factor Authentication',
            'default_locale' => 'Default Language',
        ];
    }

    /**
     * Map input keys onto the colon-namespaced settings storage keys.
     *
     * @param  list<string>|null  $only
     * @return GeneralSettingsData
     */
    public function normalize(?array $only = null): array
    {
        return [
            'app:name' => $this->string('branding.name')->toString(),
            'pterodactyl:auth:2fa_required' => $this->integer('misc.required_2fa'),
            'app:locale' => $this->string('default_locale')->toString(),
        ];
    }
}
