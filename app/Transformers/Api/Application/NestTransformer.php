<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Nest;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class NestTransformer extends Transformer
{
    /**
     * Relationships that can be loaded onto this transformation.
     *
     * @var array
     */
    protected $availableIncludes = [
        'eggs',
        'servers',
    ];

    public function getResourceName(): string
    {
        return Nest::RESOURCE_NAME;
    }

    public function transform(Nest $model): array
    {
        $response = $model->toArray();

        $response[$model->getUpdatedAtColumn()] = self::formatTimestamp($model->updated_at);
        $response[$model->getCreatedAtColumn()] = self::formatTimestamp($model->created_at);

        return $response;
    }

    /**
     * Include the Eggs relationship on the given Nest model transformation.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeEggs(Nest $model)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        return $this->collection($model->eggs, new EggTransformer());
    }

    /**
     * Include the servers relationship on the given Nest model.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeServers(Nest $model)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($model->servers, new ServerTransformer());
    }
}
