<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;

class EggVariableRepository extends EloquentRepository implements EggVariableRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return EggVariable::class;
    }
}
