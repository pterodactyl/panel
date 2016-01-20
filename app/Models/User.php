<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
