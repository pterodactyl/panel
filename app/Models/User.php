<?php

namespace Pterodactyl\Models;

use Pterodactyl\Rules\Username;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\In;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Auth\Passwords\CanResetPassword;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Pterodactyl\Notifications\SendPasswordReset as ResetPasswordNotification;

/**
 * @property int $id
 * @property string|null $external_id
 * @property string $uuid
 * @property string $username
 * @property string $email
 * @property string|null $name_first
 * @property string|null $name_last
 * @property string $password
 * @property string|null $remeber_token
 * @property string $language
 * @property bool $root_admin
 * @property bool $use_totp
 * @property string|null $totp_secret
 * @property \Carbon\Carbon|null $totp_authenticated_at
 * @property bool $gravatar
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $name
 * @property \Pterodactyl\Models\ApiKey[]|\Illuminate\Database\Eloquent\Collection $apiKeys
 * @property \Pterodactyl\Models\Server[]|\Illuminate\Database\Eloquent\Collection $servers
 * @property \Pterodactyl\Models\RecoveryToken[]|\Illuminate\Database\Eloquent\Collection $recoveryTokens
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use AvailableLanguages;
    use CanResetPassword;
    use Notifiable;

    public const USER_LEVEL_USER = 0;
    public const USER_LEVEL_ADMIN = 1;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'user';

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
    protected $dates = ['totp_authenticated_at'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'totp_secret', 'totp_authenticated_at'];

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
     * Rules verifying that the data being stored matches the expectations of the database.
     *
     * @var array
     */
    public static $validationRules = [
        'uuid' => 'required|string|size:36|unique:users,uuid',
        'email' => 'required|email|between:1,191|unique:users,email',
        'external_id' => 'sometimes|nullable|string|max:191|unique:users,external_id',
        'username' => 'required|between:1,191|unique:users,username',
        'name_first' => 'required|string|between:1,191',
        'name_last' => 'required|string|between:1,191',
        'password' => 'sometimes|nullable|string',
        'root_admin' => 'boolean',
        'language' => 'string',
        'use_totp' => 'boolean',
        'totp_secret' => 'nullable|string',
    ];

    /**
     * Implement language verification by overriding Eloquence's gather
     * rules function.
     */
    public static function getRules()
    {
        $rules = parent::getRules();

        $rules['language'][] = new In(array_keys((new self())->getAvailableLanguages()));
        $rules['username'][] = new Username();

        return $rules;
    }

    /**
     * Return the user model in a format that can be passed over to Vue templates.
     */
    public function toVueObject(): array
    {
        return (new Collection($this->toArray()))->except(['id', 'external_id'])->toArray();
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
        return trim($this->name_first . ' ' . $this->name_last);
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class)
            ->where('key_type', ApiKey::TYPE_ACCOUNT);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recoveryTokens()
    {
        return $this->hasMany(RecoveryToken::class);
    }

    /**
     * Returns all of the servers that a user can access by way of being the owner of the
     * server, or because they are assigned as a subuser for that server.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function accessibleServers()
    {
        return Server::query()
            ->select('servers.*')
            ->leftJoin('subusers', 'subusers.server_id', '=', 'servers.id')
            ->where(function (Builder $builder) {
                $builder->where('servers.owner_id', $this->id)->orWhere('subusers.user_id', $this->id);
            })
            ->groupBy('servers.id');
    }
}
