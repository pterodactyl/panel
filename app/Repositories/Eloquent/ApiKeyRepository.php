<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\APIKey;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class ApiKeyRepository extends EloquentRepository implements ApiKeyRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
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
