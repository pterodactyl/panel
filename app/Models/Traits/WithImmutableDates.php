<?php

namespace Pterodactyl\Models\Traits;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait WithImmutableDates
{
    /**
     * Converts the mutable Carbon instance into an immutable Carbon instance.
     *
     * @param mixed $value
     * @return \Carbon\CarbonImmutable
     */
    protected function asImmutableDateTime($value)
    {
        return $this->asDateTime($value)->toImmutable();
    }
}
