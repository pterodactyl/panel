<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Api;

use Pterodactyl\Models\ApiKey;

interface CreatesApiKeys
{
    /**
     * Create an API key of the given type with a fresh identifier and token. The input and
     * the permissions are validated here, so they can come straight from any caller. Each
     * allowed IP must be an IP address or a CIDR range. The permissions are only applied to
     * application keys: each is AdminAcl::NONE or AdminAcl::READ and AdminAcl::WRITE
     * combined, and a resource left out gets AdminAcl::NONE.
     *
     * @param  array<string, mixed>  $input  memo and user_id, and optionally allowed_ips
     * @param  array<string, mixed>  $permissions  the r_* column of any AdminAcl resource
     */
    public function create(int $keyType, array $input, array $permissions = []): ApiKey;
}
