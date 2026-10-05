<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Console\Commands\Location\LocationCommandsTest;

use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);
test('make creates a location', function () {
    $this->artisan('p:location:make', ['--short' => 'cli-made', '--long' => 'Made from the CLI'])->assertExitCode(0);
    $this->assertDatabaseHas('locations', ['short' => 'cli-made', 'long' => 'Made from the CLI']);
});
test('make rejects a duplicate short code', function () {
    Location::factory()->create(['short' => 'cli-dupe']);
    $this->artisan('p:location:make', ['--short' => 'cli-dupe', '--long' => 'Duplicate'])->assertExitCode(1);
    expect(Location::query()->where('short', 'cli-dupe')->count())->toBe(1);
});
test('delete removes an empty location', function () {
    $location = Location::factory()->create(['short' => 'cli-gone']);
    $this->artisan('p:location:delete', ['--short' => 'cli-gone'])->assertExitCode(0);
    $this->assertDatabaseMissing('locations', ['id' => $location->id]);
});
test('delete refuses a location with nodes', function () {
    $location = Location::factory()->create(['short' => 'cli-busy']);
    Node::factory()->create(['location_id' => $location->id]);
    expect(fn () => $this->artisan('p:location:delete', ['--short' => 'cli-busy']))
        ->toThrow(\Pterodactyl\Exceptions\Service\Location\HasActiveNodesException::class);
    $this->assertDatabaseHas('locations', ['id' => $location->id]);
});
