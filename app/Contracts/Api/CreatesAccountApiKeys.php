<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Api;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Laravel\Sanctum\NewAccessToken;
use Pterodactyl\Models\User;

interface CreatesAccountApiKeys
{
    /**
     * A user holds at most 25 account keys. The description and the allowed IPs are validated
     * here: the description is required, and each allowed IP must be an IP address or a CIDR
     * range. A key given no allowed IPs can be used from any address.
     *
     * @param  list<string>|null  $allowedIps
     *
     * @throws DisplayException
     */
    public function create(User $user, ?string $description, ?array $allowedIps): NewAccessToken;
}
