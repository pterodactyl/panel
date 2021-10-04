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
            // Check that the FQDN is a valid IP address.
            if (!filter_var(gethostbyname($this->input('fqdn')), FILTER_VALIDATE_IP)) {
                $validator->errors()->add('fqdn', trans('admin/node.validation.fqdn_not_resolvable'));
            }

            // Check that if using HTTPS the FQDN is not an IP address.
            if (filter_var($this->input('fqdn'), FILTER_VALIDATE_IP) && $this->input('scheme') === 'https') {
                $validator->errors()->add('fqdn', trans('admin/node.validation.fqdn_required_for_ssl'));
            }
        });
    }
}
