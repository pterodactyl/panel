<?php

namespace Pterodactyl\Services\Api;

use Pterodactyl\Models\APIKey;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class KeyCreationService
{
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
     * @param \Illuminate\Contracts\Encryption\Encrypter                  $encrypter
     */
    public function __construct(ApiKeyRepositoryInterface $repository, Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
        $this->repository = $repository;
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
            'identifier' => str_random(APIKey::IDENTIFIER_LENGTH),
            'token' => $this->encrypter->encrypt(str_random(APIKey::KEY_LENGTH)),
        ]);

        $instance = $this->repository->create($data, true, true);

        return $instance;
    }
}
