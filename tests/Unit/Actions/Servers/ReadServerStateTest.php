<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Actions\Servers\ReadServerStateTest;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Actions\Servers\ReadServerState;
use Pterodactyl\Contracts\Servers\ReadsServerState;
use Pterodactyl\Data\ServerState;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('cache.default', 'array');
});

function server(): Server
{
    $server = Server::factory()->make(['owner_id' => 1, 'node_id' => 1, 'egg_id' => 1, 'allocation_id' => 1]);

    return $server->setRelation('node', Node::factory()->make(['location_id' => 1]));
}

test('the action resolves from its contract', function (): void {
    expect($this->app->make(ReadsServerState::class))->toBeInstanceOf(ReadServerState::class);
});

test('live state and resource usage are mapped from Wings', function (): void {
    $fake = new FakeDaemonServer;
    $fake->details = ['state' => 'running', 'is_suspended' => true, 'utilization' => ['memory_bytes' => 1048576, 'cpu_absolute' => 12.5, 'disk_bytes' => 5242880, 'network' => ['rx_bytes' => 100, 'tx_bytes' => 200], 'uptime' => 3600]];

    $state = $this->app->make(ReadsServerState::class)->read(server());

    expect($state)->toEqual(new ServerState('running', true, 1048576, 12.5, 5242880, 100, 200, 3600));
    $fake->assertDetailsFetched();
});

test('values Wings has not measured fall back to an idle reading', function (): void {
    new FakeDaemonServer;

    expect($this->app->make(ReadsServerState::class)->read(server()))
        ->toEqual(new ServerState('stopped', false, 0, 0, 0, 0, 0, 0));
});

test('readings are cached per server under the shared resources key', function (): void {
    $fake = new FakeDaemonServer;
    $fake->details = ['state' => 'running'];
    $first = server();
    $second = server();
    $state = $this->app->make(ReadsServerState::class);

    $state->read($first);
    $fake->details = ['state' => 'offline'];

    expect($state->read($first)->state)->toBe('running')
        ->and($state->read($second)->state)->toBe('offline')
        ->and($fake->callsFor('getDetails'))->toHaveCount(2)
        ->and(Cache::get("resources:$first->uuid"))->toBe(['state' => 'running']);

    $this->travel(21)->seconds();

    expect($state->read($first)->state)->toBe('offline');
});

test('an existing resources cache entry is reused without calling Wings', function (): void {
    $fake = new FakeDaemonServer;
    $server = server();
    Cache::put("resources:$server->uuid", ['state' => 'starting', 'utilization' => ['memory_bytes' => 64]], 20);

    $state = $this->app->make(ReadsServerState::class)->read($server);

    expect($state->state)->toBe('starting')->and($state->memoryBytes)->toBe(64);
    $fake->assertNothingHappened();
});

test('failed reads are not cached', function (): void {
    Http::fake(['*/api/servers/*' => Http::sequence()->push('', 500)->push(['state' => 'running'])]);
    $server = server();
    $state = $this->app->make(ReadsServerState::class);

    expect(fn (): ServerState => $state->read($server))->toThrow(DaemonConnectionException::class);
    expect($state->read($server)->state)->toBe('running');
});
