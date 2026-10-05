<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Api;

use Pterodactyl\Models\ApiKey;

interface CreatesApiKeys
{
    /**
     * @param  ApiKeyCreationData  $data
     * @param  array<string, int>  $permissions  The r_* ACL columns, only applied to application keys.
     */
    public function create(int $keyType, array $data, array $permissions = []): ApiKey;
}
