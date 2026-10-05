<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Support\JsonValueGuard;

class EggVariableTransformer extends BaseTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(EggVariable $model): array
    {
        $attributes = $model->attributesToArray();
        JsonValueGuard::assertPayload($attributes);

        return $attributes;
    }
}
