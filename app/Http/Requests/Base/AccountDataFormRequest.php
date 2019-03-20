<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
                    'language' => array_get($modelRules, 'language'),
                    'oauth2_id' => 'sometimes',
                ];
                break;
            case 'oauth2_link':
                $rules = [
                    'oauth2_driver' => 'required|string',
                ];
                break;
            case 'oauth2_unlink':
                $rules = [
                    'oauth2_driver' => 'required|string',
                ];
                break;
            default:
                abort(422);
        }

        return $rules;
    }
}
