<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Locations;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Locations\UpdatesLocations;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class UpdateLocation implements UpdatesLocations
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * @param  ActionInput  $input
     */
    public function update(Location $location, array $input): Location
    {
        $attributes = CreateLocation::attributes($input, $location);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        DB::transaction(function () use ($location, $attributes, $extensions): void {
            $location->update($attributes);
            $this->extensions->save($location, $extensions);
        });

        return $location;
    }
}
