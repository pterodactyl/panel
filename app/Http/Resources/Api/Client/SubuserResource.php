<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Subuser;

/**
 * @extends ClientResource<Subuser>
 */
#[ResponseField('permissions', schema: ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['control.console', 'file.read']])]
class SubuserResource extends ClientResource
{
    protected array $eagerLoads = ['user'];

    public function resourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            ...UserResource::make($this->resource->loadMissing('user')->user)->toArray($request),
            'permissions' => $this->resource->permissions,
        ];
    }
}
