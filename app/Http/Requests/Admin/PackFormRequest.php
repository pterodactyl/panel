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
