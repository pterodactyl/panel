<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Database;
use League\Fractal\Resource\Item;
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
     *
     * @param \Illuminate\Contracts\Encryption\Encrypter         $encrypter
     * @param \Pterodactyl\Contracts\Extensions\HashidsInterface $hashids
     */
    public function handle(Encrypter $encrypter, HashidsInterface $hashids)
    {
        $this->encrypter = $encrypter;
        $this->hashids = $hashids;
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
            'id' => $this->hashids->encode($model->id),
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
