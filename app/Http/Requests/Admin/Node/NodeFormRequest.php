<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin\Node;

use Pterodactyl\Models\Node;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class NodeFormRequest extends AdminFormRequest
{
    /**
     * Get rules to apply to data in this request.
     */
    public function rules()
    {
        if ($this->method() === 'PATCH') {
            return Node::getRulesForUpdate($this->route()->parameter('node'));
        }

        return Node::getRules();
    }

    /**
     * Run validation after the rules above have been applied.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $scheme = $this->input('scheme');
            $fqdn = $this->input('fqdn');

            $error = Node::validateFQDN($scheme, $fqdn);
            if (!is_null($error)) {
                $validator->errors()->add('fqdn', $error);
            }
        });
    }
}
