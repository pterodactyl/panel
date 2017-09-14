<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
