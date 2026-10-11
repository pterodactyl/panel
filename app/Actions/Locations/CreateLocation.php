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
     * Validates location input and returns the model attributes it describes.
     *
     * @param  array<string, mixed>  $input
     * @return array{short: string, long: string|null}
     */
    public static function attributes(array $input, ?Location $location = null): array
    {
        $input = Validator::make($input, self::rules($location))->safe();

        return [
            'short' => $input->string('short')->toString(),
            'long' => $input->filled('long') ? $input->string('long')->toString() : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): Location
    {
        $attributes = self::attributes($input);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        return DB::transaction(function () use ($attributes, $extensions): Location {
            $location = Location::query()->create($attributes);
            $this->extensions->save($location, $extensions);

            return $location;
        });
    }
}
