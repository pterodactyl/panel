<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Api;

use Pterodactyl\Models\ApiKey;

interface UpdatesApiKeys
{
    /**
     * The token is kept. The input and the permissions are validated here, only what is given
     * changes, and the permissions only apply to application keys.
     *
     * @param  array<string, mixed>  $input  optionally memo
     * @param  array<string, mixed>  $permissions  the r_* column of any AdminAcl resource
     */
    public function update(ApiKey $key, array $input, array $permissions = []): ApiKey;
}
