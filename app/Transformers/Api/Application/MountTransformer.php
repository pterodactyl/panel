<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Mount;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class MountTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = ['eggs', 'nodes', 'servers'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Mount::RESOURCE_NAME;
    }

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

    /**
     * Return the eggs associated with this mount.
     */
    public function includeEggs(Mount $mount): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        return $this->collection($mount->eggs, new EggTransformer());
    }

    /**
     * Return the nodes associated with this mount.
     */
    public function includeNodes(Mount $mount): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->collection($mount->nodes, new NodeTransformer());
    }

    /**
     * Return the servers associated with this mount.
     */
    public function includeServers(Mount $mount): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($mount->servers, new ServerTransformer());
    }
}
