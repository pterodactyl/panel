<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class DatabaseHostTransformer extends BaseTransformer
{
    protected array $includeRelations = [
        'databases' => ['relation' => 'databases', 'transformer' => ServerDatabaseTransformer::class, 'ability' => AdminAcl::RESOURCE_SERVER_DATABASES],
    ];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = [
        'databases',
    ];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return DatabaseHost::RESOURCE_NAME;
    }

    /**
     * Transform database host into a representation for the application API.
     *
     * @return ApiPayload
     */
    public function transform(DatabaseHost $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'host' => $model->host,
            'port' => $model->port,
            'username' => $model->username,
            'node' => $model->node_id,
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at->toAtomString(),
        ];
    }

    /**
     * Include the databases associated with this host.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeDatabases(DatabaseHost $model): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVER_DATABASES)) {
            return $this->null();
        }

        $model->loadMissing('databases');

        return $this->collection($model->getRelation('databases'), $this->makeTransformer(ServerDatabaseTransformer::class), Database::RESOURCE_NAME);
    }
}
