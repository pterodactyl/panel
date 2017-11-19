<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\APIKey;

interface ApiKeyRepositoryInterface extends RepositoryInterface
{
    /**
     * Load permissions for a key onto the model.
     *
     * @param \Pterodactyl\Models\APIKey $model
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\APIKey
     */
    public function loadPermissions(APIKey $model, bool $refresh = false): APIKey;
}
