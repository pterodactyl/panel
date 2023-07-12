<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Egg;
use Pterodactyl\Transformers\Api\Transformer;

class EggTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    public function transform(Egg $model): array
    {
        return [
            'uuid' => $model->uuid,
            'name' => $model->name,
        ];
    }
}
