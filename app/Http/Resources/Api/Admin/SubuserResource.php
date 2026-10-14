<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\Subuser;

/**
 * @extends AdminResource<Subuser>
 */
class SubuserResource extends AdminResource
{
    public function resourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'server_id' => $this->resource->server_id,
            'permissions' => $this->resource->permissions,
            'created_at' => $this->formatTimestamp($this->resource->created_at),
            'updated_at' => $this->formatTimestamp($this->resource->updated_at),
        ];
    }
}
