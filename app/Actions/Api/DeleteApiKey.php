<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Api;

use Pterodactyl\Contracts\Api\DeletesApiKeys;
use Pterodactyl\Models\ApiKey;

final readonly class DeleteApiKey implements DeletesApiKeys
{
    public function delete(ApiKey $key): void
    {
        $key->delete();
    }
}
