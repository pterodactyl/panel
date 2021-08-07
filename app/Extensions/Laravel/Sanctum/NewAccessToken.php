<?php

namespace Pterodactyl\Extensions\Laravel\Sanctum;

use Pterodactyl\Models\PersonalAccessToken;
use Laravel\Sanctum\NewAccessToken as SanctumAccessToken;

/**
 * @property \Pterodactyl\Models\PersonalAccessToken $accessToken
 */
class NewAccessToken extends SanctumAccessToken
{
    /**
     * NewAccessToken constructor.
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(PersonalAccessToken $accessToken, string $plainTextToken)
    {
        $this->accessToken = $accessToken;
        $this->plainTextToken = $plainTextToken;
    }
}
