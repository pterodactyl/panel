<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Actions\Servers\ReadServerLogsTest;

use Illuminate\Support\Facades\Http;
use Pterodactyl\Actions\Servers\ReadServerLogs;
use Pterodactyl\Contracts\Servers\ReadsServerLogs;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

function server(): Server
{
    $server = Server::factory()->make(['owner_id' => 1, 'node_id' => 1, 'egg_id' => 1, 'allocation_id' => 1]);

    return $server->setRelation('node', Node::factory()->make(['location_id' => 1]));
}

test('the action resolves from its contract', function (): void {
    expect($this->app->make(ReadsServerLogs::class))->toBeInstanceOf(ReadServerLogs::class);
});

test('console lines are returned in the order Wings reports them', function (): void {
    $fake = new FakeDaemonServer;
    $fake->logs = ['first line', 'second line'];

    expect($this->app->make(ReadsServerLogs::class)->read(server(), 25))->toBe(['first line', 'second line']);
    $fake->assertLogsRead(25);
});

test('the requested line count is clamped to what Wings serves', function (?int $requested, int $sent): void {
    $fake = new FakeDaemonServer;
    $logs = $this->app->make(ReadsServerLogs::class);

    $requested === null ? $logs->read(server()) : $logs->read(server(), $requested);

    expect($fake->callsFor('getLogs'))->toHaveCount(1);
    $fake->assertLogsRead($sent);
})->with([
    'default' => [null, ReadsServerLogs::MAX_LINES],
    'above the ceiling' => [5000, ReadsServerLogs::MAX_LINES],
    'zero' => [0, 1],
    'negative' => [-10, 1],
    'within range' => [42, 42],
]);

test('a server without console output yields no lines', function (): void {
    $fake = new FakeDaemonServer;
    $fake->logs = null;

    expect($this->app->make(ReadsServerLogs::class)->read(server()))->toBe([]);
});

test('malformed Wings log responses are rejected', function (string $body): void {
    Http::fake(['*/logs*' => Http::response($body)]);

    $this->app->make(ReadsServerLogs::class)->read(server());
})->with([
    'missing data' => ['{}'],
    'data is not a list' => ['{"data":{"first":"line"}}'],
    'non-string line' => ['{"data":["line",5]}'],
    'not json' => ['offline'],
])->throws(DaemonConnectionException::class);
