<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Subusers;

use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Repository\PermissionRepositoryInterface;

class PermissionCreationService
{
    const CORE_DAEMON_PERMISSIONS = [
        's:get',
        's:console',
    ];

    /**
     * @var \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface
     */
    protected $repository;

    /**
     * PermissionCreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface $repository
     */
    public function __construct(PermissionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Assign permissions to a given subuser.
     *
     * @param int   $subuser
     * @param array $permissions
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle($subuser, array $permissions)
    {
        $permissionMappings = Permission::getPermissions(true);
        $daemonPermissions = self::CORE_DAEMON_PERMISSIONS;
        $insertPermissions = [];

        foreach ($permissions as $permission) {
            if (array_key_exists($permission, $permissionMappings)) {
                if (! is_null($permissionMappings[$permission])) {
                    array_push($daemonPermissions, $permissionMappings[$permission]);
                }

                array_push($insertPermissions, [
                    'subuser_id' => $subuser,
                    'permission' => $permission,
                ]);
            }
        }

        $this->repository->insert($insertPermissions);

        return $daemonPermissions;
    }
}
