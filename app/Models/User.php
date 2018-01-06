<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
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

    const USER_LEVEL_USER = 0;
    const USER_LEVEL_ADMIN = 1;

    const FILTER_LEVEL_ALL = 0;
    const FILTER_LEVEL_OWNER = 1;
    const FILTER_LEVEL_ADMIN = 2;
    const FILTER_LEVEL_SUBUSER = 3;

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
    protected $fillable = [
        'username',
        'email',
        'name_first',
        'name_last',
        'password',
        'language',
        'use_totp',
        'totp_secret',
        'totp_authenticated_at',
        'gravatar',
        'root_admin',
    ];

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
     * @var array
     */
    protected $dates = [self::CREATED_AT, self::UPDATED_AT, 'totp_authenticated_at'];

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
        'uuid' => 'required',
        'email' => 'required',
        'username' => 'required',
        'name_first' => 'required',
        'name_last' => 'required',
        'password' => 'sometimes',
        'language' => 'sometimes',
        'use_totp' => 'sometimes',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     *
     * @var array
     */
    protected static $dataIntegrityRules = [
        'uuid' => 'string|size:36|unique:users,uuid',
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
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
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

    /**
     * Return all of the daemon keys that a user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function keys()
    {
        return $this->hasMany(DaemonKey::class);
    }
}
