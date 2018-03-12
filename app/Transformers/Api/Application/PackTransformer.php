<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Pack;

class PackTransformer extends BaseTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return Pack::RESOURCE_NAME;
    }

    /**
     * Return a transformed User model that can be consumed by external services.
     *
     * @param \Pterodactyl\Models\Pack $pack
     * @return array
     */
    public function transform(Pack $pack): array
    {
        return [
            'id' => $pack->id,
            'uuid' => $pack->uuid,
            'egg' => $pack->egg_id,
            'name' => $pack->name,
            'description' => $pack->description,
            'is_selectable' => (bool) $pack->selectable,
            'is_visible' => (bool) $pack->visible,
            'is_locked' => (bool) $pack->locked,
            'created_at' => $this->formatTimestamp($pack->created_at),
            'updated_at' => $this->formatTimestamp($pack->updated_at),
        ];
    }
}
