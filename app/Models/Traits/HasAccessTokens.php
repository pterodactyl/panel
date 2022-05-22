<?php

namespace Pterodactyl\Models\Traits;

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Pterodactyl\Models\ApiKey;
use Laravel\Sanctum\HasApiTokens;
use Pterodactyl\Extensions\Laravel\Sanctum\NewAccessToken;

/**
 * @mixin \Pterodactyl\Models\Model
 */
trait HasAccessTokens
{
    use HasApiTokens;

    public function tokens()
    {
        return $this->hasMany(Sanctum::$personalAccessTokenModel);
    }

    public function createToken(string $name, array $abilities = ['*'])
    {
        /** @var \Pterodactyl\Models\ApiKey $token */
        $token = $this->tokens()->create([
            'user_id' => $this->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
            'identifier' => ApiKey::generateTokenIdentifier(),
            'token' => encrypt($plain = Str::random(ApiKey::KEY_LENGTH)),
            'memo' => $name,
            'allowed_ips' => [],
        ]);

        return new NewAccessToken($token, $token->identifier . $plain);
    }
}
