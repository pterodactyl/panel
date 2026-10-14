<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\ServerTransfer;

/**
 * @extends AdminResource<ServerTransfer>
 */
class ServerTransferResource extends AdminResource
{
    public function resourceName(): string
    {
        return 'server_transfer';
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'server_id' => $this->resource->server_id,
            'old_node' => $this->resource->old_node,
            'new_node' => $this->resource->new_node,
            'old_allocation' => $this->resource->old_allocation,
            'new_allocation' => $this->resource->new_allocation,
            'successful' => $this->resource->successful,
            'archived' => $this->resource->archived,
            $this->resource->getCreatedAtColumn() => $this->formatTimestamp($this->resource->created_at),
            $this->resource->getUpdatedAtColumn() => $this->formatTimestamp($this->resource->updated_at),
        ];
    }
}
