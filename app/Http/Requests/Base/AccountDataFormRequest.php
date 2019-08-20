<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Requests\Base;

use Illuminate\Support\Arr;
use App\Models\User;
use App\Http\Requests\FrontendUserFormRequest;
use App\Exceptions\Http\Base\InvalidPasswordProvidedException;

class AccountDataFormRequest extends FrontendUserFormRequest
{
    /**
     * @return bool
     * @throws \App\Exceptions\Http\Base\InvalidPasswordProvidedException
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
                    'new_email' => Arr::get($modelRules, 'email'),
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
                    'name_first' => Arr::get($modelRules, 'name_first'),
                    'name_last' => Arr::get($modelRules, 'name_last'),
                    'username' => Arr::get($modelRules, 'username'),
                    'language' => Arr::get($modelRules, 'language'),
                ];
                break;
            default:
                abort(422);
        }

        return $rules;
    }
}
