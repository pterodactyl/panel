<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Api;

use Pterodactyl\Models\ApiKey;

interface DeletesApiKeys
{
    public function delete(ApiKey $key): void;
}
