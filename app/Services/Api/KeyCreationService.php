<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Api;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class KeyCreationService
{
    const PUB_CRYPTO_LENGTH = 16;
    const PRIV_CRYPTO_LENGTH = 64;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * @var \Pterodactyl\Services\Api\PermissionService
     */
    protected $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    protected $repository;

    /**
     * ApiKeyService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     * @param \Illuminate\Database\ConnectionInterface                    $connection
     * @param \Illuminate\Contracts\Encryption\Encrypter                  $encrypter
     * @param \Pterodactyl\Services\Api\PermissionService                 $permissionService
     */
    public function __construct(
        ApiKeyRepositoryInterface $repository,
        ConnectionInterface $connection,
        Encrypter $encrypter,
        PermissionService $permissionService
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
        $this->encrypter = $encrypter;
        $this->permissionService = $permissionService;
    }

    /**
     * Create a new API Key on the system with the given permissions.
     *
     * @param array $data
     * @param array $permissions
     * @param array $administrative
     * @return string
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data, array $permissions, array $administrative = [])
    {
        $publicKey = str_random(self::PUB_CRYPTO_LENGTH);
        $secretKey = str_random(self::PRIV_CRYPTO_LENGTH);

        // Start a Transaction
        $this->connection->beginTransaction();

        $data = array_merge($data, [
            'public' => $publicKey,
            'secret' => $this->encrypter->encrypt($secretKey),
        ]);

        $instance = $this->repository->create($data, true, true);
        $nodes = $this->permissionService->getPermissions();

        foreach ($permissions as $permission) {
            @list($block, $search) = explode('-', $permission);

            if (
                (empty($block) || empty($search)) ||
                ! array_key_exists($block, $nodes['_user']) ||
                ! in_array($search, $nodes['_user'][$block])
            ) {
                continue;
            }

            $this->permissionService->create($instance->id, sprintf('user.%s', $permission));
        }

        foreach ($administrative as $permission) {
            @list($block, $search) = explode('-', $permission);

            if (
                (empty($block) || empty($search)) ||
                ! array_key_exists($block, $nodes) ||
                ! in_array($search, $nodes[$block])
            ) {
                continue;
            }

            $this->permissionService->create($instance->id, $permission);
        }

        $this->connection->commit();

        return $secretKey;
    }
}
