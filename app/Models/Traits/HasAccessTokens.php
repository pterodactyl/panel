<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Pterodactyl\Extensions\Laravel\Sanctum\NewAccessToken;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Model;

/**
 * @template TToken of \Laravel\Sanctum\Contracts\HasAbilities
 *
 * @mixin Model
 */
trait HasAccessTokens
{
    /** @use HasApiTokens<TToken> */
    use HasApiTokens {
        tokens as private _tokens;
        createToken as private _createToken;
        currentAccessToken as private _currentAccessToken;
    }

    /**
     * The tokens issued to this model.
     *
     * The concrete class is whatever is registered with
     * {@see Sanctum::usePersonalAccessTokenModel()} - {@see ApiKey} in this application.
     *
     * @return HasMany<PersonalAccessToken, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Sanctum::$personalAccessTokenModel);
    }

    /**
     * @param  list<string>|null  $ips  IP addresses the token may be used from
     */
    public function createToken(?string $memo, ?array $ips): NewAccessToken
    {
        // SAFETY: Sanctum is configured globally to persist personal access tokens with the ApiKey model.
        /** @var ApiKey $token */
        $token = $this->tokens()->forceCreate([
            'user_id' => $this->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
            'identifier' => ApiKey::generateTokenIdentifier(ApiKey::TYPE_ACCOUNT),
            'token' => encrypt($plain = Str::random(ApiKey::KEY_LENGTH)),
            'memo' => $memo ?? '',
            'allowed_ips' => $ips ?? [],
        ]);

        return new NewAccessToken($token, $plain);
    }

    /** @return TToken|null */
    public function currentAccessToken(): ?HasAbilities
    {
        return $this->_currentAccessToken();
    }
}
