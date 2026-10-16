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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\TransientToken;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Traits\HasAccessTokens;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;
use Pterodactyl\Notifications\SendPasswordReset as ResetPasswordNotification;
use Pterodactyl\Services\Users\UserAvatarService;
use Pterodactyl\Traits\Helpers\AvailableLanguages;

/**
 * @property-read int|null $subuser_of_count
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
    'avatar',
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

    public const string RESOURCE_NAME = 'user';

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
     * @return array<mixed>
     */
    public function toVueObject(): array
    {
        $attributes = $this->toArray();
        unset($attributes['id'], $attributes['external_id']);

        return [
            ...$attributes,
            'avatar' => app(UserAvatarService::class)->url($this->avatar),
            'identifier' => $this->identifier,
        ];
    }

    /**
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
     * The owner and root admins hold every permission, and root admins also receive the
     * admin websocket events. Anyone else holds what their subuser entry grants, if any.
     *
     * @return array<array-key, string>
     */
    public function permissionsOn(Server $server): array
    {
        if ($this->root_admin || $this->id === $server->owner_id) {
            $permissions = ['*'];

            if ($this->root_admin) {
                $permissions[] = 'admin.websocket.errors';
                $permissions[] = 'admin.websocket.install';
                $permissions[] = 'admin.websocket.transfer';
            }

            return $permissions;
        }

        return $server->subusers()->where('user_id', $this->id)->first()->permissions ?? [];
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
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => mb_trim($this->name_first.' '.$this->name_last),
        );
    }
}
