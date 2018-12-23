<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin;

class BaseFormRequest extends AdminFormRequest
{
    public function rules()
    {
        return [
            'company' => 'required|between:1,256',
        ];
    }
}
