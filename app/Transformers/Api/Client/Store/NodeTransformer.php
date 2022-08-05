<?php

namespace Pterodactyl\Transformers\Api\Client\Store;

use Pterodactyl\Models\Node;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;

class NodeTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Node::RESOURCE_NAME;
    }

    /**
     * Transforms the Node model into a representation that can be consumed by
     * the application api.
     */
    public function transform(Node $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
        ];
    }
}
