<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Facades\Hashids;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Models\Database;

/**
 * @extends AdminResource<Database>
 */
#[ResponseField('max_connections', 'integer', example: 0, nullable: true)]
class ServerDatabaseResource extends AdminResource
{
    protected array $eagerLoads = ['host'];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['password'];

    public function resourceName(): string
    {
        return Database::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('host');
        $host = $this->resource->host;

        return [
            'id' => Hashids::encode($this->resource->id),
            'host' => [
                'address' => $host->host,
                'port' => $host->port,
            ],
            'name' => $this->resource->database,
            'username' => $this->resource->username,
            'connections_from' => $this->resource->remote,
            'max_connections' => $this->resource->max_connections,
            'relationships' => [],
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'password' => $this->includePassword(),
            default => parent::resolveInclude($include),
        };
    }

    /**
     * No per-server subuser permission check here; admin API access is gated on root-admin alone.
     *
     * @return ApiResource<Database>
     */
    private function includePassword(): ApiResource
    {
        return $this->item($this->resource, fn (Database $model): array => [
            'password' => Arr::string(['password' => Crypt::decrypt($model->password)], 'password'),
        ], 'database_password');
    }
}
