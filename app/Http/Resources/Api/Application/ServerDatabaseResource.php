<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * @extends ApplicationResource<Database>
 */
#[ResponseField('max_connections', 'integer', example: 0, nullable: true)]
class ServerDatabaseResource extends ApplicationResource
{
    protected array $includeRelations = [
        'host' => ['relation' => 'host', 'resource' => DatabaseHostResource::class, 'ability' => AdminAcl::RESOURCE_DATABASE_HOSTS],
    ];

    protected array $availableIncludes = ['password', 'host'];

    public function resourceName(): string
    {
        return Database::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'server' => $this->resource->server_id,
            'host' => $this->resource->database_host_id,
            'database' => $this->resource->database,
            'username' => $this->resource->username,
            'remote' => $this->resource->remote,
            'max_connections' => $this->resource->max_connections,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'password' => $this->includePassword(),
            'host' => $this->includeHost(),
            default => parent::resolveInclude($include),
        };
    }

    /**
     * @return ApiResource<Database>
     */
    private function includePassword(): ApiResource
    {
        return $this->item($this->resource, fn (Database $model): array => [
            'password' => Crypt::decrypt($model->password),
        ], 'database_password');
    }

    /**
     * @return ApiResource<DatabaseHost>|NullResource
     */
    private function includeHost(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_DATABASE_HOSTS)) {
            return $this->null();
        }

        return $this->item($this->resource->loadMissing('host')->host, DatabaseHostResource::class, DatabaseHost::RESOURCE_NAME);
    }
}
