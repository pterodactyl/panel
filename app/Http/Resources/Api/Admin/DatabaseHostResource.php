<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use UnexpectedValueException;

/**
 * @extends AdminResource<DatabaseHost>
 */
#[ResponseField('max_databases', 'integer', example: 50, nullable: true)]
#[ResponseField('node_id', 'integer', example: 1, nullable: true)]
class DatabaseHostResource extends AdminResource
{
    protected array $eagerLoadCounts = ['databases'];

    protected array $includeRelations = [
        'node' => ['relation' => 'node', 'resource' => NodeResource::class],
        'databases' => ['relation' => 'databases.server'],
    ];

    protected array $availableIncludes = ['node', 'databases'];

    public function resourceName(): string
    {
        return DatabaseHost::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $host = $this->resource;

        return [
            'id' => $host->id,
            'name' => $host->name,
            'host' => $host->host,
            'port' => $host->port,
            'username' => $host->username,
            'max_databases' => $host->max_databases,
            'databases_count' => $host->databases_count ?? $host->databases()->count(),
            'node_id' => $host->node_id,
            'relationships' => [],
            'created_at' => $this->formatTimestamp($host->created_at),
            'updated_at' => $this->formatTimestamp($host->updated_at),
            ...$this->extensionFields($host),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'node' => $this->includeNode(),
            'databases' => $this->includeDatabases(),
            default => parent::resolveInclude($include),
        };
    }

    /**
     * @return ApiResource<Node>|NullResource
     */
    private function includeNode(): ApiResource|NullResource
    {
        $node = $this->resource->loadMissing('node')->node;
        if ($node === null) {
            return $this->null();
        }

        return $this->item($node, NodeResource::class, 'node');
    }

    private function includeDatabases(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('databases.server')->databases, function (Database $database): array {
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
