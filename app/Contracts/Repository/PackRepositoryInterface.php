<?php

namespace App\Contracts\Repository;

use App\Models\Pack;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Contracts\Repository\Attributes\SearchableInterface;

interface PackRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Return a pack with the associated server models attached to it.
     *
     * @param \App\Models\Pack $pack
     * @param bool                     $refresh
     * @return \App\Models\Pack
     */
    public function loadServerData(Pack $pack, bool $refresh = false): Pack;

    /**
     * Return a paginated listing of packs with their associated egg and server count.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithEggAndServerCount(): LengthAwarePaginator;
}
