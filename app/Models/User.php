<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Models;

use Hash;
use Google2FA;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Pterodactyl\Exceptions\DisplayException;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Pterodactyl\Notifications\SendPasswordReset as ResetPasswordNotification;

class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    CleansAttributes,
    ValidableContract
{
    use Authenticatable, Authorizable, CanResetPassword, Eloquence, Notifiable, Validable;

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
        'root_admin' => 'boolean',
        'use_totp' => 'boolean',
        'gravatar' => 'boolean',
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
    protected $searchableColumns = [
        'email' => 10,
        'username' => 9,
        'name_first' => 6,
        'name_last' => 6,
        'uuid' => 1,
    ];

    /**
     * Default values for specific fields in the database.
     *
     * @var array
     */
    protected $attributes = [
        'root_admin' => false,
        'language' => 'en',
        'use_totp' => false,
        'totp_secret' => null,
    ];

    /**
     * Rules verifying that the data passed in forms is valid and meets application logic rules.
     *
     * @var array
     */
    protected static $applicationRules = [
        'email' => 'required',
        'username' => 'required',
        'name_first' => 'required',
        'name_last' => 'required',
        'password' => 'sometimes',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     *
     * @var array
     */
    protected static $dataIntegrityRules = [
        'email' => 'email|unique:users,email',
        'username' => 'alpha_dash|between:1,255|unique:users,username',
        'name_first' => 'string|between:1,255',
        'name_last' => 'string|between:1,255',
        'password' => 'nullable|string',
        'root_admin' => 'boolean',
        'language' => 'string|between:2,5',
        'use_totp' => 'boolean',
        'totp_secret' => 'nullable|string',
    ];

    /**
     * Enables or disables TOTP on an account if the token is valid.
     *
     * @param int $token
     * @return bool
     * @deprecated
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
     * @param string $password
     * @param string $regex
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @deprecated
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
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Return true or false depending on wether the user is root admin or not.
     *
     * @return bool
     * @deprecated
     */
    public function isRootAdmin()
    {
        return $this->root_admin;
    }

    /**
     * Returns the user's daemon secret for a given server.
     *
     * @param \Pterodactyl\Models\Server $server
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
     * @param string $level can be all, admin, subuser, owner
     * @return $this
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
     * @param array $load
     * @return \Pterodactyl\Models\Server
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
     * Store the username as a lowecase string.
     *
     * @param string $value
     */
    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtolower($value);
    }

    /**
     * Return a concated result for the accounts full name.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->name_first . ' ' . $this->name_last;
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
