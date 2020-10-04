<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\AdminRole;

class AdminRolesRepository extends EloquentRepository
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return AdminRole::class;
    }
}
