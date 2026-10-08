<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Api;

use Pterodactyl\Models\ApiKey;

interface UpdatesApiKeys
{
    /**
     * Update an API key's attributes without regenerating its token.
     *
     * @param  ApiKeyUpdateData  $data
     * @param  array<string, int>  $permissions  The r_* ACL columns, only applied to application keys.
     */
    public function update(ApiKey $key, array $data, array $permissions = []): ApiKey;
}
