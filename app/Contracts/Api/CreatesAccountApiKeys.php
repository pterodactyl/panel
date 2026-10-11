<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Api;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Laravel\Sanctum\NewAccessToken;
use Pterodactyl\Models\User;

interface CreatesAccountApiKeys
{
    /**
     * Create an account API key for the user, who may hold at most 25. The description and
     * the allowed IPs are validated here, so they can come straight from any caller: the
     * description is required, and each allowed IP must be an IP address or a CIDR range.
     * A key given no allowed IPs can be used from any address.
     *
     * @param  list<string>|null  $allowedIps
     *
     * @throws DisplayException
     */
    public function create(User $user, ?string $description, ?array $allowedIps): NewAccessToken;
}
