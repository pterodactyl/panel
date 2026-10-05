<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use Illuminate\Support\Facades\Crypt;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Facades\Hashids;
use Pterodactyl\Models\Database;

#[ResponseField('max_connections', 'integer', example: 0, nullable: true)]
class DatabaseTransformer extends BaseClientTransformer
{
    protected array $eagerLoads = ['host', 'server.subusers'];

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
        ];
    }

    /**
     * Include the database password in the request.
     */
    public function includePassword(Database $database): Item|NullResource
    {
        if (! $this->getUser()->can(Permissions::DatabaseViewPassword->value, $database->server)) {
            return $this->null();
        }

        return $this->item($database, fn (Database $model): array => [
            'password' => Crypt::decrypt($model->password),
        ], 'database_password');
    }
}
