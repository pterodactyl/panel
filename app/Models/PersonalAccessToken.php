<?php

namespace Pterodactyl\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public const RESOURCE_NAME = 'personal_access_token';
}
