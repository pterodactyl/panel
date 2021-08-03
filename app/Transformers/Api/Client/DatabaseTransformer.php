<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Database;
use Pterodactyl\Models\Permission;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Extensions\HashidsInterface;

class DatabaseTransformer extends BaseClientTransformer
{
    protected $availableIncludes = ['password'];

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Extensions\HashidsInterface
     */
    private $hashids;

    /**
     * Handle dependency injection.
     */
    public function handle(Encrypter $encrypter, HashidsInterface $hashids)
    {
        $this->encrypter = $encrypter;
        $this->hashids = $hashids;
    }

    public function getResourceName(): string
    {
        return Database::RESOURCE_NAME;
    }

    public function transform(Database $model): array
    {
        $model->loadMissing('host');

        return [
            'id' => $this->hashids->encode($model->id),
            'host' => [
                'address' => $model->getRelation('host')->host,
                'port' => $model->getRelation('host')->port,
            ],
            'name' => $model->database,
            'username' => $model->username,
            'connections_from' => $model->remote,
            'max_connections' => $model->max_connections,
        ];
    }

    /**
     * Include the database password in the request.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includePassword(Database $database)
    {
        if (!$this->getUser()->can(Permission::ACTION_DATABASE_VIEW_PASSWORD, $database->server)) {
            return $this->null();
        }

        return $this->item($database, function (Database $model) {
            return [
                'password' => $this->encrypter->decrypt($model->password),
            ];
        }, 'database_password');
    }
}
