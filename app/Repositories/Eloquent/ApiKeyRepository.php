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
}
