<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Nodes\UpdateNodeTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Nodes\UpdatesNodes;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonConfiguration;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, bool|int|string|null> */
function fields(Node $node, array $overrides = []): array
{
    return [...['name' => $node->name, 'location_id' => $node->location_id, 'fqdn' => $node->fqdn, 'scheme' => $node->scheme, 'memory' => $node->memory, 'memory_overallocate' => $node->memory_overallocate, 'disk' => $node->disk, 'disk_overallocate' => $node->disk_overallocate, 'daemon_listen' => $node->daemonListen, 'daemon_sftp' => $node->daemonSFTP], ...$overrides];
}

test('an update keeps the optional fields it is not given', function (): void {
    $fake = new FakeDaemonConfiguration;
    $node = Node::factory()->for(Location::factory())->create(['description' => 'Kept', 'public' => false, 'behind_proxy' => true, 'maintenance_mode' => true, 'upload_size' => 512, 'daemonBase' => '/srv/volumes']);

    $this->app->make(UpdatesNodes::class)->update($node, fields($node, ['name' => 'Renamed', 'memory' => '4096', 'daemon_sftp' => '2222']));

    $node->refresh();
    expect($node->name)->toBe('Renamed')
        ->and($node->memory)->toBe(4096)
        ->and($node->daemonSFTP)->toBe(2222)
        ->and($node->description)->toBe('Kept')
        ->and($node->public)->toBeFalse()
        ->and($node->behind_proxy)->toBeTrue()
        ->and($node->maintenance_mode)->toBeTrue()
        ->and($node->upload_size)->toBe(512)
        // As over HTTP, a node given no daemon base goes back to the default one.
        ->and($node->daemonBase)->toBe('/var/lib/pterodactyl/volumes');
    $fake->assertUpdated();
});

test('nodes updated without the http api meet the same rules', function (array $input, string $field): void {
    new FakeDaemonConfiguration;
    $node = Node::factory()->for(Location::factory())->create()->refresh();
    $before = $node->getAttributes();

    try {
        $this->app->make(UpdatesNodes::class)->update($node, fields($node, $input));
        $this->fail('The node was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($node->fresh()->getAttributes())->toBe($before);
})->with([
    'a missing name' => [['name' => null], 'name'],
    'an sftp port out of range' => [['daemon_sftp' => 0], 'daemon_sftp'],
    'a maintenance flag that is not a boolean' => [['maintenance_mode' => 'on'], 'maintenance_mode'],
]);

test('a reset mints a new daemon token pair', function (): void {
    new FakeDaemonConfiguration;
    $node = Node::factory()->for(Location::factory())->create();
    $token = $node->getDecryptedKey();
    $tokenId = $node->daemon_token_id;

    $this->app->make(UpdatesNodes::class)->update($node, fields($node), resetToken: true);

    $node->refresh();
    expect($node->daemon_token_id)->not->toBe($tokenId)
        ->and($node->getDecryptedKey())->not->toBe($token);
});
