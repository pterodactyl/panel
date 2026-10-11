<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Support\JsonValueGuard;

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
     * @return ApiPayload
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->attributesToArray();
        JsonValueGuard::assertPayload($attributes);

        return $attributes;
    }
}
