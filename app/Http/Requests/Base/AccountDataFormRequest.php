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

use Pterodactyl\Models\User;
use Pterodactyl\Http\Requests\FrontendUserFormRequest;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;

class AccountDataFormRequest extends FrontendUserFormRequest
{
    /**
     * @return bool
     * @throws \Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException
     */
    public function authorize()
    {
        if (! parent::authorize()) {
            return false;
        }

        // Verify password matches when changing password or email.
        if (in_array($this->input('do_action'), ['password', 'email'])) {
            if (! password_verify($this->input('current_password'), $this->user()->password)) {
                throw new InvalidPasswordProvidedException(trans('base.account.invalid_password'));
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        $modelRules = User::getUpdateRulesForId($this->user()->id);

        switch ($this->input('do_action')) {
            case 'email':
                $rules = [
                    'new_email' => array_get($modelRules, 'email'),
                ];
                break;
            case 'password':
                $rules = [
                    'new_password' => 'required|confirmed|string|min:8',
                    'new_password_confirmation' => 'required',
                ];
                break;
            case 'identity':
                $rules = [
                    'name_first' => array_get($modelRules, 'name_first'),
                    'name_last' => array_get($modelRules, 'name_last'),
                    'username' => array_get($modelRules, 'username'),
                ];
                break;
            default:
                abort(422);
        }

        return $rules;
    }
}
