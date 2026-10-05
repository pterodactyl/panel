<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Sanctum\TransientToken;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Traits\HasAccessTokens;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;
use Pterodactyl\Notifications\SendPasswordReset as ResetPasswordNotification;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Traits\Helpers\AvailableLanguages;

/**
 * Pterodactyl\Models\User.
 *
 * @property int $id
 * @property string|null $external_id
 * @property string $uuid
 * @property string $username
 * @property string $email
 * @property string|null $name_first
 * @property string|null $name_last
 * @property string $password
 * @property string|null $remember_token
 * @property string $language
 * @property bool $root_admin
 * @property bool $use_totp
 * @property string|null $totp_secret
 * @property Carbon|null $totp_authenticated_at
 * @property bool $gravatar
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|ApiKey[] $apiKeys
 * @property int|null $api_keys_count
 * @property string $name
 * @property DatabaseNotificationCollection<int, DatabaseNotification>|DatabaseNotification[] $notifications
 * @property int|null $notifications_count
 * @property \Illuminate\Database\Eloquent\Collection|RecoveryToken[] $recoveryTokens
 * @property int|null $recovery_tokens_count
 * @property \Illuminate\Database\Eloquent\Collection|Server[] $servers
 * @property int|null $servers_count
 * @property int|null $subuser_of_count
 * @property \Illuminate\Database\Eloquent\Collection|UserSSHKey[] $sshKeys
 * @property int|null $ssh_keys_count
 * @property \Illuminate\Database\Eloquent\Collection|ApiKey[] $tokens
 * @property int|null $tokens_count
 *
 * @method static UserFactory factory(...$parameters)
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereExternalId($value)
 * @method static Builder|User whereGravatar($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereLanguage($value)
 * @method static Builder|User whereNameFirst($value)
 * @method static Builder|User whereNameLast($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereRootAdmin($value)
 * @method static Builder|User whereTotpAuthenticatedAt($value)
 * @method static Builder|User whereTotpSecret($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static Builder|User whereUseTotp($value)
 * @method static Builder|User whereUsername($value)
 * @method static Builder|User whereUuid($value)
 *
 * @mixin Model
 */
#[Attributes\Identifiable('user')]
#[Fillable([
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
])]
#[Hidden(['password', 'remember_token', 'totp_secret', 'totp_authenticated_at'])]
#[RouteKey('uuid')]
class User extends Authenticatable implements Identifiable
{
    use AvailableLanguages;

    /** @use HasAccessTokens<ApiKey|TransientToken> */
    use HasAccessTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRealtimeIdentifier;
    use HasVersion4Uuids;
    use Notifiable;

    public const int USER_LEVEL_USER = 0;

    public const int USER_LEVEL_ADMIN = 1;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'user';

    /**
     * Default values for specific fields in the database.
     */
    protected $attributes = [
        'external_id' => null,
        'root_admin' => false,
        'language' => 'en',
        'use_totp' => false,
        'totp_secret' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'root_admin' => 'boolean',
            'use_totp' => 'boolean',
            'gravatar' => 'boolean',
            'password' => 'hashed',
            'totp_authenticated_at' => 'datetime',
        ];
    }

    /**
     * The column holding the automatically generated v4 UUID for the user.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return ApiPayload
     */
    public function toVueObject(): array
    {
        $payload = Collection::make($this->toArray())->except(['id', 'external_id'])
            ->merge(['identifier' => $this->identifier])
            ->toArray();
        JsonValueGuard::assertPayload($payload);

        return $payload;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        Activity::event('auth:reset-password')
            ->withRequestMetadata()
            ->subject($this)
            ->log('sending password reset email');

        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Returns all servers that a user owns.
     *
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'owner_id');
    }

    /**
     * @return HasMany<ApiKey, $this>
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class)
            ->where('key_type', ApiKey::TYPE_ACCOUNT);
    }

    /**
     * @return HasMany<RecoveryToken, $this>
     */
    public function recoveryTokens(): HasMany
    {
        return $this->hasMany(RecoveryToken::class);
    }

    /**
     * @return HasMany<UserSSHKey, $this>
     */
    public function sshKeys(): HasMany
    {
        return $this->hasMany(UserSSHKey::class);
    }

    /**
     * Returns all the activity logs where this user is the subject - not to
     * be confused by activity logs where this user is the _actor_.
     *
     * @return MorphToMany<ActivityLog, $this>
     */
    public function activity(): MorphToMany
    {
        return $this->morphToMany(ActivityLog::class, 'subject', 'activity_log_subjects');
    }

    /**
     * Returns all the servers that a user can access by way of being the owner of the
     * server, or because they are assigned as a subuser for that server.
     *
     * @return Builder<Server>
     */
    public function accessibleServers(): Builder
    {
        return Server::query()
            ->select('servers.*')
            ->leftJoin('subusers', 'subusers.server_id', '=', 'servers.id')
            ->where(function (Builder $builder): void {
                $builder->where('servers.owner_id', $this->id)->orWhere('subusers.user_id', $this->id);
            })
            ->groupBy('servers.id');
    }

    /**
     * Store the username as a lowercase string. Reads fall through to the stored
     * column, so the get side is the string it was written as.
     *
     * @return Attribute<string, string>
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => mb_strtolower($value),
        );
    }

    /**
     * Return a concatenated result for the accounts full name.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => mb_trim($this->name_first.' '.$this->name_last),
        );
    }
}
