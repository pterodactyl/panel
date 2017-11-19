<?php

namespace Pterodactyl\Services\Api;

use Pterodactyl\Models\APIKey;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class KeyCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Services\Api\PermissionService
     */
    private $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * ApiKeyService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     * @param \Illuminate\Database\ConnectionInterface                    $connection
     * @param \Pterodactyl\Services\Api\PermissionService                 $permissionService
     */
    public function __construct(
        ApiKeyRepositoryInterface $repository,
        ConnectionInterface $connection,
        PermissionService $permissionService
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
        $this->permissionService = $permissionService;
    }

    /**
     * Create a new API Key on the system with the given permissions.
     *
     * @param array $data
     * @param array $permissions
     * @param array $administrative
     * @return \Pterodactyl\Models\APIKey
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data, array $permissions, array $administrative = []): APIKey
    {
        $token = str_random(APIKey::KEY_LENGTH);
        $data = array_merge($data, ['token' => $token]);

        $this->connection->beginTransaction();
        $instance = $this->repository->create($data, true, true);
        $nodes = $this->permissionService->getPermissions();

        foreach ($permissions as $permission) {
            @list($block, $search) = explode('-', $permission, 2);

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
            @list($block, $search) = explode('-', $permission, 2);

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

        return $instance;
    }
}
