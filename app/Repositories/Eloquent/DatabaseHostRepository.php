<?php

namespace App\Repositories\Eloquent;

use App\Models\DatabaseHost;
use Illuminate\Support\Collection;
use App\Contracts\Repository\DatabaseHostRepositoryInterface;

class DatabaseHostRepository extends EloquentRepository implements DatabaseHostRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return DatabaseHost::class;
    }

    /**
     * Return database hosts with a count of databases and the node
     * information for which it is attached.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getWithViewDetails(): Collection
    {
        return $this->getBuilder()->withCount('databases')->with('node')->get();
    }
}
