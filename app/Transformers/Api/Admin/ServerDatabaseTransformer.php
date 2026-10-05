<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Illuminate\Support\Facades\Crypt;
use League\Fractal\Resource\Item;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Facades\Hashids;
use Pterodactyl\Models\Database;

#[ResponseField('max_connections', 'integer', example: 0, nullable: true)]
class ServerDatabaseTransformer extends BaseAdminTransformer
{
    protected array $eagerLoads = ['host'];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['password'];

    public function getResourceName(): string
    {
        return Database::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(Database $model): array
    {
        $model->loadMissing('host');
        $host = $model->host;

        return [
            'id' => Hashids::encode($model->id),
            'host' => [
                'address' => $host->host,
                'port' => $host->port,
            ],
            'name' => $model->database,
            'username' => $model->username,
            'connections_from' => $model->remote,
            'max_connections' => $model->max_connections,
            'relationships' => [],
        ];
    }

    /** No per-server subuser permission check here; admin API access is gated on root-admin alone. */
    public function includePassword(Database $database): Item
    {
        return $this->item($database, fn (Database $model): array => [
            'password' => Crypt::decrypt($model->password),
        ], 'database_password');
    }
}
