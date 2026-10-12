<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Facades\Hashids;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Database;

/**
 * @extends ClientResource<Database>
 */
#[ResponseField('max_connections', 'integer', example: 0, nullable: true)]
class DatabaseResource extends ClientResource
{
    protected array $eagerLoads = ['host', 'server.subusers'];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['password'];

    public function resourceName(): string
    {
        return Database::RESOURCE_NAME;
    }

    /**
     * @return array<string, mixed>
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
     * Include the database password in the request.
     *
     * @return ApiResource<Database>|NullResource
     */
    private function includePassword(): ApiResource|NullResource
    {
        if (! $this->getUser()->can(Permissions::DatabaseViewPassword->value, $this->resource->server)) {
            return $this->null();
        }

        return $this->item($this->resource, fn (Database $model): array => [
            'password' => Crypt::decrypt($model->password),
        ], 'database_password');
    }
}
