<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class MailSettingsFormRequest extends AdminFormRequest
{
    /**
     * Return rules to validate mail settings POST data aganist.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mail_host' => 'required|string',
            'mail_port' => 'required|integer|between:1,65535',
            'mail_encryption' => 'present|string|in:"",tls,ssl',
            'mail_username' => 'string|max:255',
            'mail_password' => 'string|max:255',
            'mail_from_address' => 'required|string|email',
            'mail_from_name' => 'string|max:255',
        ];
    }

    /**
     * Override the default normalization function for this type of request
     * as we need to accept empty values on the keys.
     *
     * @param array $only
     * @return array
     */
    public function normalize($only = [])
    {
        return $this->only(array_keys($this->rules()));
    }
}
