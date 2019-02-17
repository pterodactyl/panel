<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Pterodactyl\Rules\Username;
use Illuminate\Validation\Rules\In;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Illuminate\Auth\Passwords\CanResetPassword;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
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
    use Authenticatable, Authorizable, AvailableLanguages, CanResetPassword, Eloquence, Notifiable, Validable {
        gatherRules as eloquenceGatherRules;
    }

    const USER_LEVEL_USER = 0;
    const USER_LEVEL_ADMIN = 1;

    const FILTER_LEVEL_ALL = 0;
    const FILTER_LEVEL_OWNER = 1;
    const FILTER_LEVEL_ADMIN = 2;
    const FILTER_LEVEL_SUBUSER = 3;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'user';

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
        'external_id',
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
        'oauth2_id',
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
    protected $hidden = ['password', 'remember_token', 'totp_secret', 'totp_authenticated_at'];

    /**
     * Parameters for search querying.
     *
     * @var array
     */
    protected $searchableColumns = [
        'username' => 100,
        'email' => 100,
        'external_id' => 80,
        'uuid' => 80,
        'name_first' => 40,
        'name_last' => 40,
    ];

    /**
     * Default values for specific fields in the database.
     *
     * @var array
     */
    protected $attributes = [
        'external_id' => null,
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
        'external_id' => 'sometimes',
        'username' => 'required',
        'name_first' => 'required',
        'name_last' => 'required',
        'password' => 'sometimes',
        'language' => 'sometimes',
        'use_totp' => 'sometimes',
        'oauth2_id' => 'sometimes',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     *
     * @var array
     */
    protected static $dataIntegrityRules = [
        'uuid' => 'string|size:36|unique:users,uuid',
        'email' => 'email|unique:users,email',
        'external_id' => 'nullable|string|max:255|unique:users,external_id',
        'username' => 'between:1,255|unique:users,username',
        'name_first' => 'string|between:1,255',
        'name_last' => 'string|between:1,255',
        'password' => 'nullable|string',
        'root_admin' => 'boolean',
        'language' => 'string',
        'use_totp' => 'boolean',
        'totp_secret' => 'nullable|string',
        'oauth2_id' => 'nullable|string',
    ];

    /**
     * Implement language verification by overriding Eloquence's gather
     * rules function.
     */
    protected static function gatherRules()
    {
        $rules = self::eloquenceGatherRules();
        $rules['language'][] = new In(array_keys((new self)->getAvailableLanguages()));
        $rules['username'][] = new Username;

        return $rules;
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
     * Store the username as a lowercase string.
     *
     * @param string $value
     */
    public function setUsernameAttribute(string $value)
    {
        $this->attributes['username'] = mb_strtolower($value);
    }

    /**
     * Return a concatenated result for the accounts full name.
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
