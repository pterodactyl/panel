<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\APIKey;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class ApiKeyRepository extends EloquentRepository implements ApiKeyRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return APIKey::class;
    }

    /**
     * Load permissions for a key onto the model.
     *
     * @param \Pterodactyl\Models\APIKey $model
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\APIKey
     */
    public function loadPermissions(APIKey $model, bool $refresh = false): APIKey
    {
        if (! $model->relationLoaded('permissions') || $refresh) {
            $model->load('permissions');
        }

        return $model;
    }
}
