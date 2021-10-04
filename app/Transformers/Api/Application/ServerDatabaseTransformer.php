<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Illuminate\Contracts\Encryption\Encrypter;

class ServerDatabaseTransformer extends BaseTransformer
{
    /**
     * @var array
     */
    protected $availableIncludes = ['password', 'host'];

    /**
     * @var Encrypter
     */
    private $encrypter;

    /**
     * Perform dependency injection.
     */
    public function handle(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Database::RESOURCE_NAME;
    }

    /**
     * Transform a database model in a representation for the application API.
     */
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
            'created_at' => $model->created_at->toIso8601String(),
            'updated_at' => $model->updated_at->toIso8601String(),
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
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeHost(Database $model)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_DATABASE_HOSTS)) {
            return $this->null();
        }

        $model->loadMissing('host');

        return $this->item(
            $model->getRelation('host'),
            $this->makeTransformer(DatabaseHostTransformer::class),
            DatabaseHost::RESOURCE_NAME
        );
    }
}
