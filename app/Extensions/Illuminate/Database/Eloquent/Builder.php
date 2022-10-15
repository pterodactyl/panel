<?php

namespace Pterodactyl\Extensions\Illuminate\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * Do nothing.
     */
    public function search(): self
    {
        return $this;
    }
}
