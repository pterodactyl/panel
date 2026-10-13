<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Models\Egg;

/**
 * @extends ClientResource<Egg>
 */
class EggResource extends ClientResource
{
    public function resourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
        ];
    }
}
