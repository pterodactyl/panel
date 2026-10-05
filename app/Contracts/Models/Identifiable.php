<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface Identifiable
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function whereIdentifier(Builder $builder, string $identifier): void;
}
