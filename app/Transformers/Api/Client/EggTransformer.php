<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Egg;

class EggTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    /**
     * @param \Pterodactyl\Models\Egg $egg
     * @return array
     */
    public function transform(Egg $egg)
    {
        return [
            'uuid' => $egg->uuid,
            'name' => $egg->name,
        ];
    }
}
