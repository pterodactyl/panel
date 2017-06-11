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

use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Hash;
use Pterodactyl\Contracts\Repositories\UserInterface;

class UserFormRequest extends AdminFormRequest
{
    /**
     * {@inheritdoc}
     */
    public function repository()
    {
        return UserInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        if ($this->method() === 'PATCH') {
            return [
                'email' => 'sometimes|required|email|unique:users,email,' . $this->user->id,
                'username' => 'sometimes|required|alpha_dash|between:1,255|unique:users,username, ' . $this->user->id . '|' . User::USERNAME_RULES,
                'name_first' => 'sometimes|required|string|between:1,255',
                'name_last' => 'sometimes|required|string|between:1,255',
                'password' => 'sometimes|nullable|' . User::PASSWORD_RULES,
                'root_admin' => 'sometimes|required|boolean',
                'language' => 'sometimes|required|string|min:1|max:5',
                'use_totp' => 'sometimes|required|boolean',
                'totp_secret' => 'sometimes|required|size:16',
            ];
        }

        return [
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'username' => 'required|alpha_dash|between:1,255|unique:users,username,' . $this->user->id . '|' . User::USERNAME_RULES,
            'name_first' => 'required|string|between:1,255',
            'name_last' => 'required|string|between:1,255',
            'password' => 'sometimes|nullable|' . User::PASSWORD_RULES,
            'root_admin' => 'required|boolean',
            'external_id' => 'sometimes|nullable|numeric|unique:users,external_id',
        ];
    }

    public function normalize()
    {
        if ($this->has('password')) {
            $this->merge(['password' => Hash::make($this->input('password'))]);
        }

        return parent::normalize();
    }
}
