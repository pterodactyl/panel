<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Contracts\Criteria;

use App\Repositories\Repository;

interface CriteriaInterface
{
    /**
     * Apply selected criteria to a repository call.
     *
     * @param \Illuminate\Database\Eloquent\Model  $model
     * @param \App\Repositories\Repository $repository
     * @return mixed
     */
    public function apply($model, Repository $repository);
}
