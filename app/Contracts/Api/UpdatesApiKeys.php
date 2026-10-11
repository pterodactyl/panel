<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Api;

use Pterodactyl\Models\ApiKey;

interface UpdatesApiKeys
{
    /**
     * Update an API key's attributes without regenerating its token. The input and the
     * permissions are validated here, so they can come straight from any caller. Only what
     * is given changes, and the permissions are only applied to application keys.
     *
     * @param  ActionInput  $input  optionally memo
     * @param  ActionInput  $permissions  the r_* column of any AdminAcl resource
     */
    public function update(ApiKey $key, array $input, array $permissions = []): ApiKey;
}
