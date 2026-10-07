<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Locations;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Locations\CreatesLocations;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Extensions\ExtensionFields;

final readonly class CreateLocation implements CreatesLocations
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * Create a new location from validated attributes.
     *
     * @param  LocationCreationData  $data
     */
    public function create(array $data): Location
    {
        $extensions = $data['extensions'] ?? [];
        unset($data['extensions']);

        return DB::transaction(function () use ($data, $extensions): Location {
            $location = Location::query()->create($data);
            $this->extensions->save($location, $extensions);

            return $location;
        });
    }
}
