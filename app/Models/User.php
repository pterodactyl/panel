<?php
/**
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
namespace Pterodactyl\Models;

use Hash;
use Google2FA;
use Pterodactyl\Exceptions\AccountNotFoundException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Permission;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password', 'use_totp', 'totp_secret', 'language'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'totp_secret'];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Enables or disables TOTP on an account if the token is valid.
     *
     * @param int $token The token that we want to verify.
     * @return boolean
     */
    public function toggleTotp($token)
    {

        if (!Google2FA::verifyKey($this->totp_secret, $token)) {
            return false;
        }

        $this->use_totp = !$this->use_totp;
        $this->save();

        return true;

    }

    /**
     * Set a user password to a new value assuming it meets the following requirements:
     *      - 8 or more characters in length
     *      - at least one uppercase character
     *      - at least one lowercase character
     *      - at least one number
     *
     * @param string $password The raw password to set the account password to.
     * @param string $regex The regex to use when validating the password. Defaults to '((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})'.
     * @return void
     */
    public function setPassword($password, $regex = '((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})')
    {

        if (!preg_match($regex, $password)) {
            throw new DisplayException('The password passed did not meet the minimum password requirements.');
        }

        $this->password = Hash::make($password);
        $this->save();

        return;

    }

}
