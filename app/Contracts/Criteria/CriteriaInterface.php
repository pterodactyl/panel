<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Criteria;

use Pterodactyl\Repositories\Repository;

interface CriteriaInterface
{
    /**
     * Apply selected criteria to a repository call.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return mixed
     */
    public function apply($model, Repository $repository);
}
