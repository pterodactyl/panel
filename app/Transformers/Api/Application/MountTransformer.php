<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Mount;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class MountTransformer extends BaseTransformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['eggs', 'nodes', 'servers'];

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

    /**
     * Return the eggs associated with this mount.
     *
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeEggs(Mount $mount)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $mount->loadMissing('eggs');

        return $this->collection(
            $mount->getRelation('eggs'),
            $this->makeTransformer(EggTransformer::class),
            'egg',
        );
    }

    /**
     * Return the nodes associated with this mount.
     *
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeNodes(Mount $mount)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        $mount->loadMissing('nodes');

        return $this->collection(
            $mount->getRelation('nodes'),
            $this->makeTransformer(NodeTransformer::class),
            'node',
        );
    }

    /**
     * Return the servers associated with this mount.
     *
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function includeServers(Mount $mount)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $mount->loadMissing('servers');

        return $this->collection(
            $mount->getRelation('servers'),
            $this->makeTransformer(ServerTransformer::class),
            'server',
        );
    }
}
