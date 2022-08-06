<?php

namespace Pterodactyl\Transformers\Api\Client\Store;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Allocation;
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
        $total = Allocation::where('node_id', $model->id)->count();
        $used = Allocation::where('node_id', $model->id)->where('server_id', '!=', null)->count();

        return [
            'id' => $model->id,
            'name' => $model->name,
            'fqdn' => $model->fqdn,
            'total' => $total,
            'used' => $used,
        ];
    }
}
