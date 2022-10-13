<?php

namespace Pterodactyl\Services\Api;

use Pterodactyl\Models\ApiKey;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class KeyCreationService
{
    private int $keyType = ApiKey::TYPE_NONE;

    /**
     * ApiKeyService constructor.
     */
    public function __construct(private ApiKeyRepositoryInterface $repository, private Encrypter $encrypter)
    {
    }

    /**
     * Set the type of key that should be created. By default an orphaned key will be
     * created. These keys cannot be used for anything, and will not render in the UI.
     */
    public function setKeyType(int $type): self
    {
        $this->keyType = $type;

        return $this;
    }

    /**
     * Create a new API key for the Panel using the permissions passed in the data request.
     * This will automatically generate an identifier and an encrypted token that are
     * stored in the database.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data, array $permissions = []): ApiKey
    {
        $data = array_merge($data, [
            'key_type' => $this->keyType,
            'identifier' => ApiKey::generateTokenIdentifier($this->keyType),
            'token' => $this->encrypter->encrypt(str_random(ApiKey::KEY_LENGTH)),
        ]);

        if ($this->keyType === ApiKey::TYPE_APPLICATION) {
            $data = array_merge($data, $permissions);
        }

        return $this->repository->create($data, true, true);
    }
}
