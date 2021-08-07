<?php

namespace Pterodactyl\Models\Traits;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Pterodactyl\Models\PersonalAccessToken;
use Pterodactyl\Extensions\Laravel\Sanctum\NewAccessToken;

/**
 * @mixin \Pterodactyl\Models\Model
 */
trait HasAccessTokens
{
    use HasApiTokens;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tokens()
    {
        return $this->hasMany(PersonalAccessToken::class);
    }

    /**
     * Creates a new personal access token for the user. The token will be returned
     * as the first element of the array, and the plain-text token will be the second.
     */
    public function createToken(string $description, array $abilities = ['*']): NewAccessToken
    {
        /** @var \Pterodactyl\Models\PersonalAccessToken $token */
        $token = $this->tokens()->create([
            'user_id' => $this->id,
            'description' => $description,
            'token' => hash('sha256', $plain = Str::random(PersonalAccessToken::TOKEN_LENGTH)),
            'token_id' => PersonalAccessToken::generateTokenIdentifier(),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $token->token_id . $plain);
    }
}
