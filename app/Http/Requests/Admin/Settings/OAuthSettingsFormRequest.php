<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class OAuthSettingsFormRequest extends AdminFormRequest
{
    use AvailableLanguages;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'oauth:enabled' => 'required|in:true,false',
            'oauth:drivers' => 'required|json',
            'oauth:required' => 'required|integer|in:0,1,2,3',
            'oauth:disable_other_authentication_if_required' => 'required|in:true,false',
        ];
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'oauth:enabled' => 'OAuth Enabled',
            'oauth:drivers' => 'OAuth Drivers',
            'oauth:required' => 'Require OAuth Authentication',
            'oauth:disable_other_authentication_if_required' => 'Disable Other Authentication Options If Required',
        ];
    }
}
