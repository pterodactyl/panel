<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class DatabaseHostRepository extends EloquentRepository implements DatabaseHostRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return DatabaseHost::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithViewDetails()
    {
        return $this->getBuilder()->withCount('databases')->with('node')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getWithServers($id)
    {
        Assert::numeric($id, 'First argument passed to getWithServers must be numeric, recieved %s.');

        $instance = $this->getBuilder()->with('databases.server')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }
}
