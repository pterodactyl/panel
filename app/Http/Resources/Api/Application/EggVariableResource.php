<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

/**
 * @extends ApplicationResource<EggVariable>
 */
class EggVariableResource extends ApplicationResource
{
    public function resourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource->attributesToArray();
    }
}
