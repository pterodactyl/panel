<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

use Illuminate\Validation\Rule;
use Pterodactyl\Models\Location;

final class LocationRules
{
    /**
     * Validation rules for the location requests and p:location:make.
     *
     * @param  Location|null  $ignore  the location being updated
     * @return NormalizedValidationRules
     */
    public static function rules(?Location $ignore = null): array
    {
        return [
            'short' => ['required', 'string', 'between:1,60', Rule::unique('locations', 'short')->ignore($ignore)],
            'long' => ['string', 'nullable', 'between:1,191'],
        ];
    }
}
