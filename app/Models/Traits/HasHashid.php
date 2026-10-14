<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Pterodactyl\Facades\Hashids;

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
