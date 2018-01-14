<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\ApiKey;

interface ApiKeyRepositoryInterface extends RepositoryInterface
{
    /**
     * Load permissions for a key onto the model.
     *
     * @param \Pterodactyl\Models\ApiKey $model
     * @param bool                       $refresh
     * @deprecated
     * @return \Pterodactyl\Models\ApiKey
     */
    public function loadPermissions(ApiKey $model, bool $refresh = false): ApiKey;
}
