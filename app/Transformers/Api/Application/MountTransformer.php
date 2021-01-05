<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Mount;

class MountTransformer extends BaseTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return Mount::RESOURCE_NAME;
    }

    /**
     * Return a transformed Mount model that can be consumed by external services.
     *
     * @param \Pterodactyl\Models\Mount $model
     * @return array
     */
    public function transform(Mount $model): array
    {
        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'name' => $model->name,
            'description' => $model->description,
            'source' => $model->source,
            'target' => $model->target,
            'read_only' => $model->read_only,
            'user_mountable' => $model->user_mountable,
        ];
    }
}
