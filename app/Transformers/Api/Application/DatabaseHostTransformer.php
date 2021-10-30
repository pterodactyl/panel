<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class DatabaseHostTransformer extends Transformer
{
    /**
     * @var array
     */
    protected $availableIncludes = [
        'databases',
    ];

    public function getResourceName(): string
    {
        return DatabaseHost::RESOURCE_NAME;
    }

    public function transform(DatabaseHost $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'host' => $model->host,
            'port' => $model->port,
            'username' => $model->username,
            // @phpstan-ignore-next-line no clue why it can't find this.
            'node' => $model->node_id,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Include the databases associated with this host.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeDatabases(DatabaseHost $model)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVER_DATABASES)) {
            return $this->null();
        }

        return $this->collection($model->databases, new ServerDatabaseTransformer());
    }
}
