<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

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
     * @return array<string, mixed>
     */
    public function transform(EggVariable $model): array
    {
        return $model->attributesToArray();
    }
}
