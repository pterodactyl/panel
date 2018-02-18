<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class AdvancedSettingsFormRequest extends AdminFormRequest
{
    /**
     * Return all of the rules to apply to this request's data.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'recaptcha:enabled' => 'required|in:true,false',
            'recaptcha:secret_key' => 'required|string|max:255',
            'recaptcha:website_key' => 'required|string|max:255',
            'pterodactyl:guzzle:timeout' => 'required|integer|between:1,60',
            'pterodactyl:guzzle:connect_timeout' => 'required|integer|between:1,60',
            'pterodactyl:console:count' => 'required|integer|min:1',
            'pterodactyl:console:frequency' => 'required|integer|min:10',
        ];
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'recaptcha:enabled' => 'reCAPTCHA Enabled',
            'recaptcha:secret_key' => 'reCAPTCHA Secret Key',
            'recaptcha:website_key' => 'reCAPTCHA Website Key',
            'pterodactyl:guzzle:timeout' => 'HTTP Request Timeout',
            'pterodactyl:guzzle:connect_timeout' => 'HTTP Connection Timeout',
            'pterodactyl:console:count' => 'Console Message Count',
            'pterodactyl:console:frequency' => 'Console Frequency Tick',
        ];
    }
}
