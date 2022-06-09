<?php

namespace Pterodactyl\Transformers\Api\Client\Store;

use Pterodactyl\Models\Egg;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;

class EggTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    /**
     * Transform an Egg model into a representation that can be consumed by
     * the application api.
     *
     * @return array
     */
    public function transform(Egg $model)
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'docker_images' => $model->docker_images,
        ];
    }
}
