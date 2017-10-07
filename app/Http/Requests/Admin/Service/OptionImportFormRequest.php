<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin\Service;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class OptionImportFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'import_file' => 'bail|required|file|max:1000|mimetypes:application/json,text/plain',
            'import_to_service' => 'bail|required|integer|exists:services,id',
        ];
    }
}
