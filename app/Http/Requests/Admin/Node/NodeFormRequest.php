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
            // Note, this function will also resolve CNAMEs for us automatically,
            // there is no need to manually resolve them here.
            //
            // Using @ as workaround to fix https://bugs.php.net/bug.php?id=73149
            $records = @dns_get_record($this->input('fqdn'), DNS_A + DNS_AAAA);
            if (empty($records)) {
                $validator->errors()->add('fqdn', trans('admin/node.validation.fqdn_not_resolvable'));
            }

            // Check that if using HTTPS the FQDN is not an IP address.
            if (filter_var($this->input('fqdn'), FILTER_VALIDATE_IP) && $this->input('scheme') === 'https') {
                $validator->errors()->add('fqdn', trans('admin/node.validation.fqdn_required_for_ssl'));
            }
        });
    }
}
