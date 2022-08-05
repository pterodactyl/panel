<?php

namespace Pterodactyl\Transformers\Api\Client\Store;

use Pterodactyl\Models\Nest;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;

class NestTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Nest::RESOURCE_NAME;
    }

    /**
     * Transforms the Nest model into a representation that can be consumed by
     * the application api.
     */
    public function transform(Nest $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
        ];
    }
}
