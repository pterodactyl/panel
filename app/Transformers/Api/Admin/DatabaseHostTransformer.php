<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;
use UnexpectedValueException;

#[ResponseField('max_databases', 'integer', example: 50, nullable: true)]
#[ResponseField('node_id', 'integer', example: 1, nullable: true)]
class DatabaseHostTransformer extends BaseAdminTransformer
{
    protected array $eagerLoadCounts = ['databases'];

    protected array $includeRelations = [
        'node' => ['relation' => 'node', 'transformer' => NodeTransformer::class],
        'databases' => ['relation' => 'databases.server'],
    ];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['node', 'databases'];

    public function getResourceName(): string
    {
        return DatabaseHost::RESOURCE_NAME;
    }

    /** The host password is never exposed through the API surface. */
    /**
     * @return ApiPayload
     */
    public function transform(DatabaseHost $model): array
    {
        $payload = [
            'id' => $model->id,
            'name' => $model->name,
            'host' => $model->host,
            'port' => $model->port,
            'username' => $model->username,
            'max_databases' => $model->max_databases,
            'databases_count' => $model->databases_count ?? $model->databases()->count(),
            'node_id' => $model->node_id,
            'relationships' => [],
            'created_at' => $this->formatTimestamp($model->created_at),
            'updated_at' => $this->formatTimestamp($model->updated_at),
            ...$this->extensionFields($model),
        ];
        JsonValueGuard::assertPayload($payload);

        return $payload;
    }

    public function includeNode(DatabaseHost $model): Item|NullResource
    {
        $model->loadMissing('node');

        if (! $model->getRelation('node')) {
            return $this->null();
        }

        return $this->item(
            $model->getRelation('node'),
            $this->makeTransformer(NodeTransformer::class),
            'node'
        );
    }

    public function includeDatabases(DatabaseHost $model): Collection|NullResource
    {
        $model->loadMissing('databases.server');

        return $this->collection($model->getRelation('databases'), function (Database $database): array {
            $server = $database->getRelation('server');
            throw_unless($server instanceof Server, UnexpectedValueException::class, 'Database transformations require the server relation.');

            return [
                'server' => [
                    'id' => $server->id,
                    'name' => $server->name,
                ],
                'name' => $database->database,
                'username' => $database->username,
                'connections_from' => $database->remote,
                'max_connections' => $database->max_connections,
            ];
        }, Database::RESOURCE_NAME);
    }
}
