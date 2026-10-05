<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\WingsJsonResponseTest;

use Illuminate\Support\Facades\Http;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\TestCase;
use Throwable;

uses(TestCase::class);

/** @return array<array-key, mixed> */
function wingsResponse(string $operation, string $body): array
{
    Http::fake(['*/api/*' => Http::response($body, 200, ['X-Request-Id' => 'invalid-response-123'])]);
    $node = Node::factory()->make(['location_id' => 1]);
    $server = Server::factory()->make(['owner_id' => 1, 'node_id' => 1, 'egg_id' => 1, 'allocation_id' => 1]);
    $server->setRelation('node', $node);

    return match ($operation) {
        'system' => Daemon::node($node)->systemInformation(),
        'details' => Daemon::server($server)->details(),
        'directory' => Daemon::server($server)->files()->getDirectory('/'),
        'compress' => Daemon::server($server)->files()->compressFiles('/', ['file.txt']),
    };
}

test('malformed Wings responses use the gateway error contract', function (string $operation, string $body): void {
    try {
        wingsResponse($operation, $body);
        $this->fail('Expected an invalid Wings response to be rejected.');
    } catch (DaemonConnectionException $daemonConnectionException) {
        expect($daemonConnectionException->getStatusCode())->toBe(502)
            ->and($daemonConnectionException->getRequestId())->toBe('invalid-response-123')
            ->and($daemonConnectionException->getPrevious())->toBeInstanceOf(Throwable::class);
    }
})->with(['system', 'details', 'directory', 'compress'])->with(['not-json', '"offline"', 'null']);

test('Wings rejects invalid types before consumers run', function (string $operation, string $body): void {
    expect(fn (): array => wingsResponse($operation, $body))->toThrow(DaemonConnectionException::class);
})->with([
    ['details', '{"utilization":{"network":"offline"}}'],
    ['details', '{"utilization":{"memory_bytes":"1024"}}'],
    ['directory', '{"unexpected":{}}'],
    ['directory', '["file.txt"]'],
    ['directory', '[{"created":"invalid-date"}]'],
    ['compress', '{"size":"large"}'],
    ['compress', '[1]'],
]);

test('Wings preserves valid typed response values', function (string $operation, array $payload): void {
    expect(wingsResponse($operation, json_encode($payload, JSON_THROW_ON_ERROR)))->toBe($payload);
})->with([
    ['system', ['version' => '1.0.0']],
    ['details', ['state' => 'running', 'utilization' => ['memory_bytes' => 1024, 'cpu_absolute' => 2.5]]],
    ['directory', [['name' => 'file.txt', 'size' => 42, 'file' => true]]],
    ['compress', ['name' => 'archive.tar.gz', 'size' => 42, 'file' => true]],
]);
