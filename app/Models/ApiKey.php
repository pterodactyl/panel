<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\Guard;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * @property list<string>|null $allowed_ips
 * @property-read User $user
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

    public const string RESOURCE_NAME = 'api_key';

    public const int TYPE_NONE = 0;

    public const int TYPE_ACCOUNT = 1;

    public const int TYPE_APPLICATION = 2;

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

    public static function findToken(string $token): ?self
    {
        $identifier = mb_substr($token, 0, self::IDENTIFIER_LENGTH);

        $model = static::query()->where('identifier', $identifier)->first();
        if (($model) !== null && decrypt($model->token) === mb_substr($token, mb_strlen($identifier))) {
            return $model;
        }

        return null;
    }

    public static function getPrefixForType(int $type): string
    {
        throw_unless(in_array($type, [self::TYPE_ACCOUNT, self::TYPE_APPLICATION], true), InvalidArgumentException::class, "Unsupported API key type [$type].");

        return $type === self::TYPE_ACCOUNT ? 'ptlc_' : 'ptla_';
    }

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
