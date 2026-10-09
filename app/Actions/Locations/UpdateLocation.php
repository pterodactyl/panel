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
     * Update an existing location from validated attributes.
     *
     * @param  LocationUpdateData  $data
     */
    public function update(Location $location, array $data): Location
    {
        $extensions = ValidatedExtensionValues::of($data['extensions'] ?? null);
        unset($data['extensions']);

        DB::transaction(function () use ($location, $data, $extensions): void {
            $location->update($data);
            $this->extensions->save($location, $extensions);
        });

        return $location;
    }
}
