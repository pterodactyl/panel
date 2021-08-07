<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Transformers\Api\Transformer;

class AllocationTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return Allocation::RESOURCE_NAME;
    }

    public function transform(Allocation $model): array
    {
        return [
            'id' => $model->id,
            'ip' => $model->ip,
            'ip_alias' => $model->ip_alias,
            'port' => $model->port,
            'notes' => $model->notes,
            'is_default' => $model->server->allocation_id === $model->id,
        ];
    }
}
