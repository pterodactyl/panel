<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Facades\Hashids;

/**
 * Expose the model's primary key as a hashid encoded string.
 *
 * @property-read string $hashid
 *
 * @mixin Model
 */
trait HasHashid
{
    /**
     * @return Attribute<string, never>
     */
    protected function hashid(): Attribute
    {
        return Attribute::get(fn (): string => Hashids::encode($this->getKey()));
    }
}
