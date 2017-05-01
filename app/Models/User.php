<?php
/**
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

namespace Pterodactyl\Models;

use Hash;
use Google2FA;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Pterodactyl\Exceptions\DisplayException;
use Nicolaslopezj\Searchable\SearchableTrait;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Pterodactyl\Notifications\SendPasswordReset as ResetPasswordNotification;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, Notifiable, SearchableTrait;

    /**
     * The rules for user passwords.
     *
     * @var string
     */
    const PASSWORD_RULES = 'regex:((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})';

    /**
     * The regex rules for usernames.
     *
     * @var string
     */
    const USERNAME_RULES = 'regex:/^([\w\d\.\-]{1,255})$/';

    /**
     * Level of servers to display when using access() on a user.
     *
     * @var string
     */
    protected $accessLevel = 'all';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * A list of mass-assignable variables.
     *
     * @var array
     */
    protected $fillable = ['username', 'email', 'name_first', 'name_last', 'password', 'language', 'use_totp', 'totp_secret', 'gravatar', 'root_admin'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'root_admin' => 'integer',
        'use_totp' => 'integer',
        'gravatar' => 'integer',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'totp_secret'];

    /**
     * Parameters for search querying.
     *
     * @var array
     */
    protected $searchable = [
        'columns' => [
            'email' => 10,
            'username' => 9,
            'name_first' => 6,
            'name_last' => 6,
            'uuid' => 1,
        ],
    ];

    protected $query;

    /**
     * Enables or disables TOTP on an account if the token is valid.
     *
     * @param  int  $token
     * @return bool
     */
    public function toggleTotp($token)
    {
        if (! Google2FA::verifyKey($this->totp_secret, $token, 1)) {
            return false;
        }

        $this->use_totp = ! $this->use_totp;

        return $this->save();
    }

    /**
     * Set a user password to a new value assuming it meets the following requirements:
     *      - 8 or more characters in length
     *      - at least one uppercase character
     *      - at least one lowercase character
     *      - at least one number.
     *
     * @param  string  $password
     * @param  string  $regex
     * @return void
     */
    public function setPassword($password, $regex = '((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})')
    {
        if (! preg_match($regex, $password)) {
            throw new DisplayException('The password passed did not meet the minimum password requirements.');
        }

        $this->password = Hash::make($password);
        $this->save();
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Return true or false depending on wether the user is root admin or not.
     *
     * @return bool
     */
    public function isRootAdmin()
    {
        return $this->root_admin === 1;
    }

    /**
     * Returns the user's daemon secret for a given server.
     *
     * @param  \Pterodactyl\Models\Server  $server
     * @return null|string
     */
    public function daemonToken(Server $server)
    {
        if ($this->id === $server->owner_id || $this->isRootAdmin()) {
            return $server->daemonSecret;
        }

        $subuser = $this->subuserOf->where('server_id', $server->id)->first();

        return ($subuser) ? $subuser->daemonSecret : null;
    }

    /**
     * Returns an array of all servers a user is able to access.
     * Note: does not account for user admin status.
     *
     * @return array
     */
    public function serverAccessArray()
    {
        return Server::select('id')->where('owner_id', $this->id)->union(
            Subuser::select('server_id')->where('user_id', $this->id)
        )->pluck('id')->all();
    }

    /**
     * Change the access level for a given call to `access()` on the user.
     *
     * @param  string  $level can be all, admin, subuser, owner
     * @return void
     */
    public function setAccessLevel($level = 'all')
    {
        if (! in_array($level, ['all', 'admin', 'subuser', 'owner'])) {
            $level = 'all';
        }
        $this->accessLevel = $level;

        return $this;
    }

    /**
     * Returns an array of all servers a user is able to access.
     * Note: does not account for user admin status.
     *
     * @param  array        $load
     * @return \Illuiminate\Database\Eloquent\Builder
     */
    public function access(...$load)
    {
        if (count($load) > 0 && is_null($load[0])) {
            $query = Server::query();
        } else {
            $query = Server::with(! empty($load) ? $load : ['service', 'node', 'allocation']);
        }

        // If access level is set to owner, only display servers
        // that the user owns.
        if ($this->accessLevel === 'owner') {
            $query->where('owner_id', $this->id);
        }

        // If set to all, display all servers they can access, including
        // those they access as an admin.
        //
        // If set to subuser, only return the servers they can access because
        // they are owner, or marked as a subuser of the server.
        if (($this->accessLevel === 'all' && ! $this->isRootAdmin()) || $this->accessLevel === 'subuser') {
            $query->whereIn('id', $this->serverAccessArray());
        }

        // If set to admin, only display the servers a user can access
        // as an administrator (leaves out owned and subuser of).
        if ($this->accessLevel === 'admin' && $this->isRootAdmin()) {
            $query->whereNotIn('id', $this->serverAccessArray());
        }

        return $query;
    }

    /**
     * Returns all permissions that a user has.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function permissions()
    {
        return $this->hasManyThrough(Permission::class, Subuser::class);
    }

    /**
     * Returns all servers that a user owns.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class, 'owner_id');
    }

    /**
     * Return all servers that user is listed as a subuser of directly.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subuserOf()
    {
        return $this->hasMany(Subuser::class);
    }
}
