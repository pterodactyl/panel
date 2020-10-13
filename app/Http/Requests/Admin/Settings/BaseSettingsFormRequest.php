<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Illuminate\Validation\Rule;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class BaseSettingsFormRequest extends AdminFormRequest
{
    use AvailableLanguages;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'app:name' => 'required|string|max:191',
            'pterodactyl:auth:2fa_required' => 'required|integer|in:0,1,2',
            'app:locale' => ['required', 'string', Rule::in(array_keys($this->getAvailableLanguages()))],
            'app:analytics' => 'nullable|string',
        ];
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'app:name' => 'Company Name',
            'pterodactyl:auth:2fa_required' => 'Require 2-Factor Authentication',
            'app:locale' => 'Default Language',
            'app:analytics' => 'Google Analytics',
        ];
    }
}
