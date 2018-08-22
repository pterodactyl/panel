<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Database;
use League\Fractal\Resource\Item;
use Illuminate\Contracts\Encryption\Encrypter;

class DatabaseTransformer extends BaseClientTransformer
{
    protected $availableIncludes = ['password'];

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * Handle dependency injection.
     *
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     */
    public function handle(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return Database::RESOURCE_NAME;
    }

    /**
     * @param \Pterodactyl\Models\Database $model
     * @return array
     */
    public function transform(Database $model): array
    {
        $model->loadMissing('host');

        return [
            'host' => [
                'address' => $model->getRelation('host')->host,
                'port' => $model->getRelation('host')->port,
            ],
            'name' => $model->database,
            'username' => $model->username,
            'connections_from' => $model->remote,
        ];
    }

    /**
     * Include the database password in the request.
     *
     * @param \Pterodactyl\Models\Database $model
     * @return \League\Fractal\Resource\Item
     */
    public function includePassword(Database $model): Item
    {
        return $this->item($model, function (Database $model) {
            return [
                'password' => $this->encrypter->decrypt($model->password),
            ];
        }, 'database_password');
    }
}
