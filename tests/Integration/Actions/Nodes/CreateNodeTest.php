<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Nodes\CreateNodeTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Nodes\CreatesNodes;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, bool|int|string|null> */
function node(Location $location, array $overrides = []): array
{
    return [...['name' => 'Direct', 'location_id' => $location->id, 'fqdn' => 'node.example.com', 'scheme' => 'https', 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'daemon_listen' => 8080, 'daemon_sftp' => 2022], ...$overrides];
}

test('nodes created without the http api meet the same rules', function (array $input, string $field): void {
    $count = Node::query()->count();

    try {
        $this->app->make(CreatesNodes::class)->create(node(Location::factory()->create(), $input));
        $this->fail('The node was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect(Node::query()->count())->toBe($count);
})->with([
    'a missing name' => [['name' => null], 'name'],
    'a name with a slash' => [['name' => 'bad/name'], 'name'],
    'an unknown location' => [['location_id' => 0], 'location_id'],
    'a missing scheme' => [['scheme' => null], 'scheme'],
    'memory that is not a number' => [['memory' => 'lots'], 'memory'],
    'an overallocation below -1' => [['disk_overallocate' => -2], 'disk_overallocate'],
    'a listen port out of range' => [['daemon_listen' => 70000], 'daemon_listen'],
    'a missing sftp port' => [['daemon_sftp' => null], 'daemon_sftp'],
    'a relative daemon base' => [['daemon_base' => 'volumes'], 'daemon_base'],
    'an upload size of zero' => [['upload_size' => 0], 'upload_size'],
    'a public flag that is not a boolean' => [['public' => 'yes'], 'public'],
]);

test('values are coerced the way laravel validates them', function (int|string $flag, bool $expected): void {
    $location = Location::factory()->create();

    $node = $this->app->make(CreatesNodes::class)->create(node($location, [
        'location_id' => (string) $location->id,
        'public' => $flag,
        'behind_proxy' => $flag,
        'maintenance_mode' => $flag,
        'memory' => '4096',
        'disk_overallocate' => '-1',
        'daemon_listen' => '8443',
        'daemon_sftp' => '2222',
        'upload_size' => '256',
    ]));

    expect($node->location_id)->toBe($location->id)
        ->and($node->public)->toBe($expected)
        ->and($node->behind_proxy)->toBe($expected)
        ->and($node->maintenance_mode)->toBe($expected)
        ->and($node->memory)->toBe(4096)
        ->and($node->disk_overallocate)->toBe(-1)
        ->and($node->daemonListen)->toBe(8443)
        ->and($node->daemonSFTP)->toBe(2222)
        ->and($node->upload_size)->toBe(256);
})->with([[1, true], ['1', true], [0, false], ['0', false]]);

test('a node given only the required fields takes the defaults', function (): void {
    $node = $this->app->make(CreatesNodes::class)->create(node(Location::factory()->create(), ['description' => '']));

    expect($node->daemonBase)->toBe('/var/lib/pterodactyl/volumes')
        ->and($node->upload_size)->toBe(100)
        ->and($node->description)->toBeNull()
        ->and($node->public)->toBeTrue()
        ->and($node->behind_proxy)->toBeFalse()
        ->and($node->maintenance_mode)->toBeFalse();
});

test('p:node:make meets the node rules', function (): void {
    $location = Location::factory()->create();
    $options = ['--name' => 'Console', '--description' => 'Made on the command line.', '--locationId' => (string) $location->id, '--fqdn' => 'node.example.com', '--public' => '0', '--scheme' => 'https', '--proxy' => '0', '--maintenance' => '0', '--maxMemory' => '2048', '--overallocateMemory' => '0', '--maxDisk' => '20480', '--overallocateDisk' => '0', '--uploadSize' => '100', '--daemonListeningPort' => '8080', '--daemonSFTPPort' => '2022', '--daemonBase' => '/srv/volumes'];

    $this->artisan('p:node:make', [...$options, '--daemonListeningPort' => '70000'])
        ->expectsOutputToContain('daemon listen')
        ->assertExitCode(1);
    $this->assertDatabaseMissing('nodes', ['name' => 'Console']);

    $this->artisan('p:node:make', $options)->assertExitCode(0);
    $this->assertDatabaseHas('nodes', ['name' => 'Console', 'location_id' => $location->id, 'public' => false, 'daemonListen' => 8080, 'daemonBase' => '/srv/volumes']);
});
