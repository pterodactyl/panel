<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\Guard;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * Pterodactyl\Models\ApiKey.
 *
 * @property int $id
 * @property int $user_id
 * @property int $key_type
 * @property string $identifier
 * @property string $token
 * @property list<string>|null $allowed_ips
 * @property string|null $memo
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $r_servers
 * @property int $r_nodes
 * @property int $r_allocations
 * @property int $r_users
 * @property int $r_locations
 * @property int $r_eggs
 * @property int $r_database_hosts
 * @property int $r_server_databases
 * @property User $tokenable
 * @property User $user
 *
 * @method static ApiKeyFactory factory(...$parameters)
 * @method static Builder|ApiKey newModelQuery()
 * @method static Builder|ApiKey newQuery()
 * @method static Builder|ApiKey query()
 * @method static Builder|ApiKey whereAllowedIps($value)
 * @method static Builder|ApiKey whereCreatedAt($value)
 * @method static Builder|ApiKey whereId($value)
 * @method static Builder|ApiKey whereIdentifier($value)
 * @method static Builder|ApiKey whereKeyType($value)
 * @method static Builder|ApiKey whereLastUsedAt($value)
 * @method static Builder|ApiKey whereMemo($value)
 * @method static Builder|ApiKey whereRAllocations($value)
 * @method static Builder|ApiKey whereRDatabaseHosts($value)
 * @method static Builder|ApiKey whereREggs($value)
 * @method static Builder|ApiKey whereRLocations($value)
 * @method static Builder|ApiKey whereRNodes($value)
 * @method static Builder|ApiKey whereRServerDatabases($value)
 * @method static Builder|ApiKey whereRServers($value)
 * @method static Builder|ApiKey whereRUsers($value)
 * @method static Builder|ApiKey whereToken($value)
 * @method static Builder|ApiKey whereUpdatedAt($value)
 * @method static Builder|ApiKey whereUserId($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
#[Fillable([
    'identifier',
    'token',
    'allowed_ips',
    'memo',
    'last_used_at',
    'expires_at',
])]
#[Hidden(['token'])]
class ApiKey extends Model implements HasAbilities
{
    /** @use HasFactory<ApiKeyFactory> */
    use HasFactory;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'api_key';

    /**
     * Different API keys that can exist on the system.
     */
    public const int TYPE_NONE = 0;

    public const int TYPE_ACCOUNT = 1;

    public const int TYPE_APPLICATION = 2;

    /**
     * The length of API key identifiers.
     */
    public const int IDENTIFIER_LENGTH = 16;

    /**
     * The length of the actual API key that is encrypted and stored
     * in the database.
     */
    public const int KEY_LENGTH = 32;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_ips' => 'array',
            'user_id' => 'int',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            self::CREATED_AT => 'datetime',
            self::UPDATED_AT => 'datetime',
            'r_'.AdminAcl::RESOURCE_USERS => 'int',
            'r_'.AdminAcl::RESOURCE_ALLOCATIONS => 'int',
            'r_'.AdminAcl::RESOURCE_DATABASE_HOSTS => 'int',
            'r_'.AdminAcl::RESOURCE_SERVER_DATABASES => 'int',
            'r_'.AdminAcl::RESOURCE_EGGS => 'int',
            'r_'.AdminAcl::RESOURCE_LOCATIONS => 'int',
            'r_'.AdminAcl::RESOURCE_NODES => 'int',
            'r_'.AdminAcl::RESOURCE_SERVERS => 'int',
        ];
    }

    /**
     * Finds the model matching the provided token.
     */
    public static function findToken(string $token): ?self
    {
        $identifier = mb_substr($token, 0, self::IDENTIFIER_LENGTH);

        $model = static::query()->where('identifier', $identifier)->first();
        if (($model) !== null && decrypt($model->token) === mb_substr($token, mb_strlen($identifier))) {
            return $model;
        }

        return null;
    }

    /**
     * Returns the standard prefix for API keys in the system.
     */
    public static function getPrefixForType(int $type): string
    {
        throw_unless(in_array($type, [self::TYPE_ACCOUNT, self::TYPE_APPLICATION], true), InvalidArgumentException::class, "Unsupported API key type [$type].");

        return $type === self::TYPE_ACCOUNT ? 'ptlc_' : 'ptla_';
    }

    /**
     * Generates a new identifier for an API key.
     */
    public static function generateTokenIdentifier(int $type): string
    {
        $prefix = self::getPrefixForType($type);

        return $prefix.Str::random(self::IDENTIFIER_LENGTH - mb_strlen($prefix));
    }

    public function can($ability): bool
    {
        // todo: this was never initially implemented and only became obvious once
        //  internal tooling was updated and started catching this mistake.
        return false;
    }

    public function cant($ability): bool
    {
        return ! $this->can($ability);
    }

    /**
     * Returns the user this token is assigned to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Required for support with Laravel Sanctum.
     *
     * @return BelongsTo<User, $this>
     *
     * @see Guard::supportsTokens()
     */
    public function tokenable(): BelongsTo
    {
        return $this->user();
    }
}
