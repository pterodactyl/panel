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

namespace Pterodactyl\Services;

use Pterodactyl\Models\APIKey;
use Illuminate\Database\Connection;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Exceptions\Model\DataValidationException;

class ApiKeyService
{
    const PUB_CRYPTO_BYTES = 8;
    const PRIV_CRYPTO_BYTES = 32;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * @var \Pterodactyl\Models\APIKey
     */
    protected $model;

    /**
     * @var \Pterodactyl\Services\ApiPermissionService
     */
    protected $permissionService;

    /**
     * ApiKeyService constructor.
     *
     * @param \Pterodactyl\Models\APIKey                 $model
     * @param \Illuminate\Database\Connection            $database
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     * @param \Pterodactyl\Services\ApiPermissionService $permissionService
     */
    public function __construct(
        APIKey $model,
        Connection $database,
        Encrypter $encrypter,
        ApiPermissionService $permissionService
    ) {
        $this->database = $database;
        $this->encrypter = $encrypter;
        $this->model = $model;
        $this->permissionService = $permissionService;
    }

    /**
     * Create a new API Key on the system with the given permissions.
     *
     * @param  array  $data
     * @param  array  $permissions
     * @param  array  $administrative
     * @return string
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create(array $data, array $permissions, array $administrative = [])
    {
        $publicKey = bin2hex(random_bytes(self::PUB_CRYPTO_BYTES));
        $secretKey = bin2hex(random_bytes(self::PRIV_CRYPTO_BYTES));

        // Start a Transaction
        $this->database->beginTransaction();

        $instance = $this->model->newInstance($data);
        $instance->public = $publicKey;
        $instance->secret = $this->encrypter->encrypt($secretKey);

        if (! $instance->save()) {
            $this->database->rollBack();
            throw new DataValidationException($instance->getValidator());
        }

        $key = $instance->fresh();
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

            $this->permissionService->create($key->id, sprintf('user.%s', $permission));
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

            $this->permissionService->create($key->id, $permission);
        }

        $this->database->commit();

        return $secretKey;
    }

    /**
     * Delete the API key and associated permissions from the database.
     *
     * @param  int|\Pterodactyl\Models\APIKey $key
     * @return bool|null
     *
     * @throws \Exception
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function revoke($key)
    {
        if (! $key instanceof APIKey) {
            $key = $this->model->findOrFail($key);
        }

        return $key->delete();
    }
}
