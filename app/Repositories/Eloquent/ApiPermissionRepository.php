<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\APIPermission;
use Pterodactyl\Contracts\Repository\ApiPermissionRepositoryInterface;

class ApiPermissionRepository extends EloquentRepository implements ApiPermissionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return APIPermission::class;
    }
}
