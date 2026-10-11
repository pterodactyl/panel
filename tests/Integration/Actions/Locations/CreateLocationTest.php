<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Locations\CreateLocationTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Pterodactyl\Contracts\Locations\CreatesLocations;
use Pterodactyl\Contracts\Locations\UpdatesLocations;
use Pterodactyl\Models\Location;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('locations created or updated without the http api meet the same rules', function (): void {
    $locations = $this->app->make(CreatesLocations::class);
    $taken = Location::factory()->create(['short' => 'taken']);

    expect(fn () => $locations->create(['short' => 'taken']))->toThrow(ValidationException::class)
        ->and(fn () => $locations->create(['short' => str_repeat('a', 61)]))->toThrow(ValidationException::class);

    $location = $locations->create(['short' => 'fresh', 'long' => '']);
    expect($location->long)->toBeNull();

    // A location keeps its own short code when it is updated.
    $this->app->make(UpdatesLocations::class)->update($taken, ['short' => 'taken', 'long' => 'Still here']);
    expect($taken->refresh()->long)->toBe('Still here');
});

test('extension field values that no request validated are refused', function (): void {
    expect(fn () => $this->app->make(CreatesLocations::class)->create(['short' => 'ext', 'extensions' => ['billing' => ['plan' => 'pro']]]))
        ->toThrow(InvalidArgumentException::class, 'must come from a request that validated them');
});
