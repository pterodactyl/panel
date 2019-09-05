<?php

namespace Pterodactyl\Models\Traits;

use Pterodactyl\Extensions\Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }
}
