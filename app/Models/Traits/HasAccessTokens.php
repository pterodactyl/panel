<?php

namespace Pterodactyl\Models\Traits;

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Pterodactyl\Models\ApiKey;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pterodactyl\Extensions\Laravel\Sanctum\NewAccessToken;

/**
 * @template TToken of \Laravel\Sanctum\Contracts\HasAbilities
 *
 * @mixin \Pterodactyl\Models\Model
 */
trait HasAccessTokens
{
    /** @use \Laravel\Sanctum\HasApiTokens<TToken> */
    use HasApiTokens {
        tokens as private _tokens;
        createToken as private _createToken;
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Sanctum::$personalAccessTokenModel);
    }

    public function createToken(?string $memo, ?array $ips, ?array $permissions = null, ?array $servers = null): NewAccessToken
    {
        /** @var ApiKey $token */
        $token = $this->tokens()->forceCreate([
            'user_id' => $this->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
            'identifier' => ApiKey::generateTokenIdentifier(ApiKey::TYPE_ACCOUNT),
            'token' => encrypt($plain = Str::random(ApiKey::KEY_LENGTH)),
            'memo' => $memo ?? '',
            'allowed_ips' => $ips ?? [],
            'permissions' => $permissions,
            'allowed_servers' => $servers,
        ]);

        return new NewAccessToken($token, $plain);
    }

    /**
     * Returns the client API key used to authenticate the current request, or
     * null if the request was not authenticated with a client API key (e.g. a
     * session, or an application key).
     */
    public function currentApiKey(): ?ApiKey
    {
        $token = $this->currentAccessToken();

        return $token instanceof ApiKey && $token->key_type === ApiKey::TYPE_ACCOUNT ? $token : null;
    }
}
