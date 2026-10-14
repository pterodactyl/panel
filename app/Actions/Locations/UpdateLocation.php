<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Locations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Locations\UpdatesLocations;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class UpdateLocation implements UpdatesLocations
{
    public function __construct(private ExtensionFields $extensions) {}

    public function update(Location $location, array $input): Location
    {
        $validated = Validator::validate($input, CreateLocation::rules($location));
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        DB::transaction(function () use ($location, $validated, $extensions): void {
            // A location given no long name, or a blank one, has none.
            $location->update([...$validated, 'long' => filled($validated['long'] ?? null) ? $validated['long'] : null]);
            $this->extensions->save($location, $extensions);
        });

        return $location;
    }
}
