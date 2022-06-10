<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Egg;

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
     * @return array
     */
    public function transform(Egg $egg)
    {
        return [
            'name' => $egg->name,
        ];
    }
}
