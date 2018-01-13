<?php

namespace Pterodactyl\Services\Api;

use Pterodactyl\Models\APIKey;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class KeyCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * ApiKeyService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     * @param \Illuminate\Database\ConnectionInterface                    $connection
     * @param \Illuminate\Contracts\Encryption\Encrypter                  $encrypter
     */
    public function __construct(
        ApiKeyRepositoryInterface $repository,
        ConnectionInterface $connection,
        Encrypter $encrypter
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
        $this->encrypter = $encrypter;
    }

    /**
     * Create a new API key for the Panel using the permissions passed in the data request.
     * This will automatically generate an identifer and an encrypted token that are
     * stored in the database.
     *
     * @param array $data
     * @return \Pterodactyl\Models\APIKey
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data): APIKey
    {
        $data = array_merge($data, [
            'identifer' => str_random(APIKey::IDENTIFIER_LENGTH),
            'token' => $this->encrypter->encrypt(str_random(APIKey::KEY_LENGTH)),
        ]);

        $instance = $this->repository->create($data, true, true);

        return $instance;
    }
}
