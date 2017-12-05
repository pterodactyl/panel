<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin\Egg;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class EggImportFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            'import_file' => 'bail|required|file|max:1000|mimetypes:application/json,text/plain',
        ];

        if ($this->method() !== 'PUT') {
            $rules['import_to_nest'] = 'bail|required|integer|exists:nests,id';
        }

        return $rules;
    }
}
