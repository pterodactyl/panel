<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\ServerTransfer;

class ServerTransferTransformer extends BaseTransformer
{
    public function getResourceName(): string
    {
        return ServerTransfer::RESOURCE_NAME;
    }

    public function transform(ServerTransfer $model): array
    {
        return [
            'id' => $model->id,
            'server_id' => $model->server_id,
            'successful' => $model->successful,
            'archived' => $model->archived,
            'node' => [
                'old' => $model->old_node,
                'new' => $model->new_node,
            ],
            'allocations' => [
                'old' => $model->new_allocation,
                'new' => $model->old_allocation,
            ],
            'additional_allocations' => [
                'old' => $model->old_additional_allocations,
                'new' => $model->new_additional_allocations,
            ]
        ];
    }
}
