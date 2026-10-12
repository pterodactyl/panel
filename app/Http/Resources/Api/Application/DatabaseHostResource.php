<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * @extends ApplicationResource<DatabaseHost>
 */
class DatabaseHostResource extends ApplicationResource
{
    protected array $includeRelations = [
        'databases' => ['relation' => 'databases', 'resource' => ServerDatabaseResource::class, 'ability' => AdminAcl::RESOURCE_SERVER_DATABASES],
    ];

    protected array $availableIncludes = ['databases'];

    public function resourceName(): string
    {
        return DatabaseHost::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'host' => $this->resource->host,
            'port' => $this->resource->port,
            'username' => $this->resource->username,
            'node' => $this->resource->node_id,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'databases' => $this->includeDatabases(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeDatabases(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVER_DATABASES)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('databases')->databases, ServerDatabaseResource::class, Database::RESOURCE_NAME);
    }
}
