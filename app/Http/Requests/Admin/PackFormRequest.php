<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\Pack;
use Pterodactyl\Services\Packs\PackCreationService;

class PackFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        if ($this->method() === 'PATCH') {
            return Pack::getUpdateRulesForId($this->route()->parameter('pack')->id);
        }

        return Pack::getCreateRules();
    }

    /**
     * Run validation after the rules above have been applied.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        if ($this->method() !== 'POST') {
            return;
        }

        $validator->after(function ($validator) {
            $mimetypes = implode(',', PackCreationService::VALID_UPLOAD_TYPES);

            /* @var $validator \Illuminate\Validation\Validator */
            $validator->sometimes('file_upload', 'sometimes|required|file|mimetypes:' . $mimetypes, function () {
                return true;
            });
        });
    }
}
