<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Contracts\Repository\PermissionRepositoryInterface;

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
