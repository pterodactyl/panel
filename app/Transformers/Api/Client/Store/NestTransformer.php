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
     * Transform an Egg model into a representation that can be consumed by
     * the application api.
     *
     * @return array
     */
    public function transform(Nest $model)
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
        ];
    }
}
