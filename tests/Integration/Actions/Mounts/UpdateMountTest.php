<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Mounts\UpdateMountTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Mounts\UpdatesMounts;
use Pterodactyl\Models\Mount;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, bool|int|string|null> */
function fields(Mount $mount, array $overrides = []): array
{
    return [...['name' => $mount->name, 'source' => $mount->source, 'target' => $mount->target], ...$overrides];
}

test('an update keeps the optional fields it is not given', function (): void {
    $mount = Mount::factory()->create(['description' => 'Kept', 'read_only' => true, 'user_mountable' => true]);

    $this->app->make(UpdatesMounts::class)->update($mount, fields($mount, ['name' => 'Renamed', 'source' => '/mnt/renamed']));

    $mount->refresh();
    expect($mount->name)->toBe('Renamed')
        ->and($mount->source)->toBe('/mnt/renamed')
        ->and($mount->description)->toBe('Kept')
        ->and($mount->read_only)->toBeTrue()
        ->and($mount->user_mountable)->toBeTrue();
});

test('a mount keeps its own name, and given fields replace the stored ones', function (): void {
    $mount = Mount::factory()->create(['name' => 'Mine', 'description' => 'Old', 'read_only' => true]);

    $this->app->make(UpdatesMounts::class)->update($mount, fields($mount, ['description' => null, 'read_only' => '0', 'user_mountable' => 1]));

    $mount->refresh();
    expect($mount->name)->toBe('Mine')
        ->and($mount->description)->toBeNull()
        ->and($mount->read_only)->toBeFalse()
        ->and($mount->user_mountable)->toBeTrue();
});

test('mounts updated without the http api meet the same rules', function (array $input, string $field): void {
    Mount::factory()->create(['name' => 'Taken']);
    $mount = Mount::factory()->create()->refresh();
    $before = $mount->getAttributes();

    try {
        $this->app->make(UpdatesMounts::class)->update($mount, fields($mount, $input));
        $this->fail('The mount was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($mount->fresh()->getAttributes())->toBe($before);
})->with([
    'a taken name' => [['name' => 'Taken'], 'name'],
    'a source within the wings configuration' => [['source' => '/var/lib/pterodactyl/volumes/../../../../etc/pterodactyl'], 'source'],
    'a missing target' => [['target' => null], 'target'],
    'a user mountable flag that is not a boolean' => [['user_mountable' => 'on'], 'user_mountable'],
]);
