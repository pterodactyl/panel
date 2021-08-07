<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Database;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;
use Illuminate\Contracts\Encryption\Encrypter;

class ServerDatabaseTransformer extends Transformer
{
    /**
     * @var array
     */
    protected $availableIncludes = ['password', 'host'];

    protected Encrypter $encrypter;

    public function handle(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    public function getResourceName(): string
    {
        return Database::RESOURCE_NAME;
    }

    public function transform(Database $model): array
    {
        return [
            'id' => $model->id,
            'server' => $model->server_id,
            'host' => $model->database_host_id,
            'database' => $model->database,
            'username' => $model->username,
            'remote' => $model->remote,
            'max_connections' => $model->max_connections,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Include the database password in the request.
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includePassword(Database $model)
    {
        return $this->item($model, function (Database $model) {
            return [
                'password' => $this->encrypter->decrypt($model->password),
            ];
        }, 'database_password');
    }

    /**
     * Return the database host relationship for this server database.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeHost(Database $model)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_DATABASE_HOSTS)) {
            return $this->null();
        }

        return $this->item($model->host, new DatabaseHostTransformer());
    }
}
