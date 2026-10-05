<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Api;

use Pterodactyl\Extensions\Laravel\Sanctum\NewAccessToken;
use Pterodactyl\Models\User;

interface CreatesAccountApiKeys
{
    /** @param list<string>|null $allowedIps */
    public function create(User $user, ?string $description, ?array $allowedIps): NewAccessToken;
}
