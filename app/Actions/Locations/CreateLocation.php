<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Locations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Locations\CreatesLocations;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class CreateLocation implements CreatesLocations
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * A location's fields. Pass the location being updated so its own short code is not
     * taken for a duplicate.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?Location $location = null): array
    {
        return [
            'short' => ['required', 'string', 'between:1,60', Rule::unique('locations', 'short')->ignore($location)],
            'long' => ['string', 'nullable', 'between:1,191'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): Location
    {
        $validated = Validator::validate($input, self::rules());
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        return DB::transaction(function () use ($validated, $extensions): Location {
            $location = Location::query()->create([...$validated, 'long' => filled($validated['long'] ?? null) ? $validated['long'] : null]);
            $this->extensions->save($location, $extensions);

            return $location;
        });
    }
}
