<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Pack;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PackRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a pack with the associated server models attached to it.
     *
     * @param \Pterodactyl\Models\Pack $pack
     * @param bool $refresh
     * @return \Pterodactyl\Models\Pack
     */
    public function loadServerData(Pack $pack, bool $refresh = false): Pack;

    /**
     * Return a paginated listing of packs with their associated egg and server count.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithEggAndServerCount(): LengthAwarePaginator;
}
