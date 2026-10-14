<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Api;

use Pterodactyl\Contracts\Api\DeletesApiKeys;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\ApiKey;

final readonly class DeleteApiKey implements DeletesApiKeys
{
    public function delete(ApiKey $key): void
    {
        $key->delete();

        // Application keys are panel resources, logged by the admin API; an account key
        // belongs on its owner's feed.
        if ($key->key_type === ApiKey::TYPE_ACCOUNT) {
            Activity::event('user:api-key.delete')->subject($key->user)->property('identifier', $key->identifier)->log();
        }
    }
}
