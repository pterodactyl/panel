<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Base;

use IPTools\Network;
use Pterodactyl\Http\Requests\FrontendUserFormRequest;

class ApiKeyFormRequest extends FrontendUserFormRequest
{
    /**
     * Rules applied to data passed in this request.
     *
     * @return array
     */
    public function rules()
    {
        $this->parseAllowedIntoArray();

        return [
            'memo' => 'required|nullable|string|max:500',
            'permissions' => 'sometimes|present|array',
            'admin_permissions' => 'sometimes|present|array',
            'allowed_ips' => 'present',
            'allowed_ips.*' => 'sometimes|string',
        ];
    }

    /**
     * Parse the string of allowed IPs into an array.
     */
    protected function parseAllowedIntoArray()
    {
        $loop = [];
        if (! empty($this->input('allowed_ips'))) {
            foreach (explode(PHP_EOL, $this->input('allowed_ips')) as $ip) {
                $loop[] = trim($ip);
            }
        }

        $this->merge(['allowed_ips' => $loop]);
    }

    /**
     * Run additional validation rules on the request to ensure all of the data is good.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            /* @var \Illuminate\Validation\Validator $validator */
            if (empty($this->input('permissions')) && empty($this->input('admin_permissions'))) {
                $validator->errors()->add('permissions', 'At least one permission must be selected.');
            }

            foreach ($this->input('allowed_ips') as $ip) {
                $ip = trim($ip);

                try {
                    Network::parse($ip);
                } catch (\Exception $ex) {
                    $validator->errors()->add('allowed_ips', 'Could not parse IP ' . $ip . ' because it is in an invalid format.');
                }
            }
        });
    }
}
