<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
