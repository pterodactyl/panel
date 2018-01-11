<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\APIPermission;
use Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface;

class ApiPermissionRepository extends EloquentRepository implements ApiPermissionRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return APIPermission::class;
    }
}
