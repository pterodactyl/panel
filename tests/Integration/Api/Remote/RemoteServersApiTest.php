<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Remote\RemoteServersApiTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class);
uses(DatabaseTransactions::class);
test('requests without a token are rejected', function () {
    $this->getJson('/api/remote/servers')->assertUnauthorized();
});
test('node can list its own servers', function () {
    $server = $this->createServerModel();
    // A server on a different node must never be included.
    $other = $this->createServerModel();
    $response = actingAsNode($server->node)->getJson('/api/remote/servers')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.uuid', $server->uuid);
    $this->assertNotSame($other->node_id, $server->node_id);
});
test('node can fetch a single server configuration', function () {
    $server = $this->createServerModel();
    actingAsNode($server->node)->getJson("/api/remote/servers/{$server->uuid}")->assertOk()->assertJsonStructure(['settings', 'process_configuration'])->assertJsonPath('settings.uuid', $server->uuid);
});
test('node cannot fetch a server it does not own', function () {
    $server = $this->createServerModel();
    $otherNode = Node::factory()->for(Location::factory())->create();
    actingAsNode($otherNode)->getJson("/api/remote/servers/{$server->uuid}")->assertForbidden();
});
test('node can fetch install script details', function () {
    $server = $this->createServerModel();
    actingAsNode($server->node)->getJson("/api/remote/servers/{$server->uuid}/install")->assertOk()->assertJsonStructure(['container_image', 'entrypoint', 'script']);
});
test('node can report a completed install', function () {
    $server = $this->createServerModel(['status' => Server::STATUS_INSTALLING, 'installed_at' => null]);
    actingAsNode($server->node)->postJson("/api/remote/servers/{$server->uuid}/install", ['successful' => true, 'reinstall' => false])->assertNoContent();
    $server->refresh();
    expect($server->status)->toBeNull();
    expect($server->installed_at)->not->toBeNull();
});
test('node can report a failed install', function () {
    $server = $this->createServerModel(['status' => Server::STATUS_INSTALLING, 'installed_at' => null]);
    actingAsNode($server->node)->postJson("/api/remote/servers/{$server->uuid}/install", ['successful' => false, 'reinstall' => false])->assertNoContent();
    expect($server->refresh()->status)->toBe(Server::STATUS_INSTALL_FAILED);
});
test('node can reset restoring servers', function () {
    $server = $this->createServerModel();
    $server->update(['status' => Server::STATUS_RESTORING_BACKUP]);
    actingAsNode($server->node)->postJson('/api/remote/servers/reset')->assertNoContent();
    expect($server->refresh()->status)->toBeNull();
});
test('node can push activity events', function () {
    $server = $this->createServerModel();
    $user = User::factory()->create();
    actingAsNode($server->node)->postJson('/api/remote/activity', ['data' => [['server' => $server->uuid, 'user' => $user->uuid, 'event' => 'server:power.start', 'metadata' => null, 'ip' => '10.0.0.5', 'timestamp' => now()->toRfc3339String()]]])->assertOk();
    $this->assertDatabaseHas('activity_logs', ['event' => 'server:power.start', 'actor_id' => $user->id]);
});
test('node can mark a backup as completed', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $server->id, 'is_successful' => false, 'completed_at' => null]);
    actingAsNode($server->node)->postJson("/api/remote/backups/{$backup->uuid}", ['successful' => true, 'checksum' => 'abc123', 'checksum_type' => 'sha1', 'size' => 1024])->assertNoContent();
    $backup->refresh();
    expect($backup->is_successful)->toBeTrue();
    expect($backup->checksum)->toBe('sha1:abc123');
    expect($backup->bytes)->toBe(1024);
    expect($backup->completed_at)->not->toBeNull();
});
test('completed backup cannot be reported again', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $server->id, 'is_successful' => true]);
    actingAsNode($server->node)->postJson("/api/remote/backups/{$backup->uuid}", ['successful' => true, 'checksum' => 'x', 'checksum_type' => 'sha1', 'size' => 1])->assertBadRequest();
});
test('node can mark a backup restore as complete', function () {
    $server = $this->createServerModel();
    $server->update(['status' => Server::STATUS_RESTORING_BACKUP]);
    $backup = Backup::factory()->create(['server_id' => $server->id]);
    actingAsNode($server->node)->postJson("/api/remote/backups/{$backup->uuid}/restore", ['successful' => true])->assertNoContent();
    expect($server->refresh()->status)->toBeNull();
    expect($server->activity()->where('event', 'server:backup.restore-complete')->exists())->toBeTrue();
});
test('node can mark a backup restore as failed', function () {
    $server = $this->createServerModel();
    $server->update(['status' => Server::STATUS_RESTORING_BACKUP]);
    $backup = Backup::factory()->create(['server_id' => $server->id]);
    actingAsNode($server->node)->postJson("/api/remote/backups/{$backup->uuid}/restore", ['successful' => false])->assertNoContent();
    expect($server->refresh()->status)->toBeNull();
    expect($server->activity()->where('event', 'server:backup.restore-failed')->exists())->toBeTrue();
});
test('backup upload url requires size and s3 adapter', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $server->id, 'is_successful' => false, 'completed_at' => null]);
    actingAsNode($server->node)->getJson("/api/remote/backups/{$backup->uuid}")->assertBadRequest();
    // The default backup adapter is wings, which does not support generating
    // multipart upload URLs.
    actingAsNode($server->node)->getJson("/api/remote/backups/{$backup->uuid}?size=1024")->assertBadRequest();
});
function actingAsNode(Node $node): IntegrationTestCase
{
    return (function () use ($node) {
        return $this->withHeader('Authorization', "Bearer {$node->daemon_token_id}.".$node->getDecryptedKey());
    })->call(pterodactylTestCase());
}

test('reporting a failed backup restore records a namespaced server event', function () {
    $server = $this->createServerModel(['status' => Server::STATUS_RESTORING_BACKUP]);
    $backup = Backup::factory()->create(['server_id' => $server->id]);

    actingAsNode($server->node)->postJson("/api/remote/backups/{$backup->uuid}/restore", ['successful' => false])->assertNoContent();

    expect($server->activity()->where('event', 'server:backup.restore-failed')->exists())->toBeTrue();
});

test('wings configurations batch variables and keep per server overrides', function () {
    $first = $this->createServerModel();
    $servers = [$first];
    for ($i = 1; $i < 10; $i++) {
        $servers[] = $this->createServerModel(['node_id' => $first->node_id, 'egg_id' => $first->egg_id]);
    }
    $variable = $first->egg->variables()->firstOrFail();
    foreach ($servers as $i => $server) {
        ServerVariable::query()->create(['server_id' => $server->id, 'variable_id' => $variable->id, 'variable_value' => 'wings-'.$i]);
    }
    $request = fn (int $limit) => actingAsNode($first->node)->getJson('/api/remote/servers?per_page='.$limit);
    $request(1)->assertOk();
    $counts = [];
    foreach ([1, 10] as $limit) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $request($limit)->assertOk()->assertJsonCount($limit, 'data');
            $counts[] = count(array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'select')));
        } finally {
            DB::disableQueryLog();
        }
    }
    expect($counts[1])->toBe($counts[0]);
    $response = $request(10)->assertOk();
    foreach ($servers as $i => $server) {
        $row = collect($response->json('data'))->firstWhere('uuid', $server->uuid);
        expect($row['settings']['environment'][$variable->env_variable])->toBe('wings-'.$i);
    }
});

test('resetting restoring servers loads backup subjects together', function () {
    $first = $this->createServerModel();
    $servers = [$first];
    for ($i = 1; $i < 10; $i++) {
        $servers[] = $this->createServerModel(['node_id' => $first->node_id]);
    }
    foreach ($servers as $server) {
        $server->update(['status' => Server::STATUS_RESTORING_BACKUP]);
        $backup = Backup::factory()->create(['server_id' => $server->id]);
        Activity::event('server:backup.restore')->subject($server, $backup)->log();
    }
    DB::flushQueryLog();
    DB::enableQueryLog();
    try {
        actingAsNode($first->node)->postJson('/api/remote/servers/reset')->assertNoContent();
        $reads = array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'select') && str_contains($query['query'], 'from `backups`'));
        expect($reads)->toHaveCount(1);
    } finally {
        DB::disableQueryLog();
    }
    foreach ($servers as $server) {
        expect($server->fresh()->status)->toBeNull();
        expect($server->activity()->where('event', 'server:backup.restore-failed')->exists())->toBeTrue();
    }
});
