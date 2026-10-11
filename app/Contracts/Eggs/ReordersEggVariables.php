<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\Egg;

interface ReordersEggVariables
{
    /**
     * Give the egg's variables the sort order of their position in the list. The ids are
     * validated here, so they can come straight from any caller: each is an integer or a
     * numeric string, listed once. An id of a variable on another egg is skipped.
     *
     * @param  list<mixed>  $order  the variable ids in their new order
     */
    public function reorder(Egg $egg, array $order): void;
}
