<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\ServerTransfer;

class ServerTransferTransformer extends BaseAdminTransformer
{
    public function getResourceName(): string
    {
        return 'server_transfer';
    }

    /**
     * @return ApiPayload
     */
    public function transform(ServerTransfer $model): array
    {
        return [
            'id' => $model->id,
            'server_id' => $model->server_id,
            'old_node' => $model->old_node,
            'new_node' => $model->new_node,
            'old_allocation' => $model->old_allocation,
            'new_allocation' => $model->new_allocation,
            'successful' => $model->successful,
            'archived' => $model->archived,
            $model->getCreatedAtColumn() => $this->formatTimestamp($model->created_at),
            $model->getUpdatedAtColumn() => $this->formatTimestamp($model->updated_at),
        ];
    }
}
