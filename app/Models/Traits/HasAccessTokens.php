<?php

namespace Pterodactyl\Models\Traits;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Pterodactyl\Models\PersonalAccessToken;

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
     *
     * @param string $description
     * @param string[] $abilities
     * @return array
     */
    public function createToken(string $description, array $abilities = ['*']): array
    {
        /** @var \Pterodactyl\Models\PersonalAccessToken $token */
        $token = $this->tokens()->create([
            'user_id' => $this->id,
            'description' => $description,
            'token' => hash('sha256', $plain = Str::random(36)),
            'token_id' => 'ptdl_' . Str::random(11),
            'abilities' => $abilities,
        ]);

        return [$token, $token->token_id . $plain];
    }
}
