<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Illuminate\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends EloquentBuilder<TModel>
 */
class Builder extends EloquentBuilder
{
    /**
     * Do nothing.
     *
     * @return self<TModel>
     */
    public function search(): self
    {
        return $this;
    }
}
