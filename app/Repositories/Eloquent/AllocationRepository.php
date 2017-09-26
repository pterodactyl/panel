<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class AllocationRepository extends EloquentRepository implements AllocationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Allocation::class;
    }

    /**
     * {@inheritdoc}
     */
    public function assignAllocationsToServer($server, array $ids)
    {
        return $this->getBuilder()->whereIn('id', $ids)->update(['server_id' => $server]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllocationsForNode($node)
    {
        return $this->getBuilder()->where('node_id', $node)->get();
    }
}
