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
            'mail:host' => 'required|string',
            'mail:port' => 'required|integer|between:1,65535',
            'mail:encryption' => 'present|string|in:"",tls,ssl',
            'mail:username' => 'string|max:255',
            'mail:password' => 'string|max:255',
            'mail:from:address' => 'required|string|email',
            'mail:from:name' => 'string|max:255',
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
        $keys = array_flip(array_keys($this->rules()));

        if (empty($this->input('mail:password'))) {
            unset($keys['mail:password']);
        }

        return $this->only(array_flip($keys));
    }
}
