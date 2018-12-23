<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Repository\PermissionRepositoryInterface;

class PermissionRepository extends EloquentRepository implements PermissionRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Permission::class;
    }
}
