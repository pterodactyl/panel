<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Api;

use Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface;

class PermissionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface
     */
    protected $repository;

    /**
     * ApiPermissionService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface $repository
     */
    public function __construct(ApiPermissionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Store a permission key in the database.
     *
     * @param string $key
     * @param string $permission
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create($key, $permission)
    {
        // @todo handle an array of permissions to do a mass assignment?
        return $this->repository->withoutFreshModel()->create([
            'key_id' => $key,
            'permission' => $permission,
        ]);
    }

    /**
     * Return all of the permissions available for an API Key.
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->repository->getModel()::CONST_PERMISSIONS;
    }
}
