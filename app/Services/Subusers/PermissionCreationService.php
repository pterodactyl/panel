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
