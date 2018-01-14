<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\ApiKey;
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
        return ApiKey::class;
    }

    /**
     * Load permissions for a key onto the model.
     *
     * @param \Pterodactyl\Models\ApiKey $model
     * @param bool                       $refresh
     * @deprecated
     * @return \Pterodactyl\Models\ApiKey
     */
    public function loadPermissions(ApiKey $model, bool $refresh = false): ApiKey
    {
        if (! $model->relationLoaded('permissions') || $refresh) {
            $model->load('permissions');
        }

        return $model;
    }
}
