<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Jobs\RevokeSftpAccessJobTest;

use Illuminate\Http\Client\ConnectionException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonRevocation;
use Throwable;

uses(IntegrationTestCase::class);
test('unique id based on model type', function (string $class, string $key) {
    $model = $class::factory()->make(['uuid' => 'uuid-1234']);
    $job = new RevokeSftpAccessJob('user-1', $model);
    expect($job->uniqueId())->toEqual("revoke-sftp:user-1:{$key}:uuid-1234");
})->with([
    'server' => [Server::class, 'server'],
    'node' => [Node::class, 'node'],
]);
test('job releases back to queue on failure', function () {
    $node = Node::factory()->make(['uuid' => 'uuid-1234']);
    $fake = new FakeDaemonRevocation;
    $fake->throwable = new DaemonConnectionException(new ConnectionException('Connection failed'));
    $job = new class('user-1', $node) extends RevokeSftpAccessJob
    {
        /** @var list<int> */
        public array $releasedDelays = [];

        public function release($delay = 0): void
        {
            $this->releasedDelays[] = $delay;
        }
    };
    expect(fn () => $job->handle())->not->toThrow(Throwable::class);
    expect($job->releasedDelays)->toBe([10]);
    $fake->assertDeauthorized('user-1');
});
test('job dispatches for node', function () {
    $node = Node::factory()->make(['uuid' => 'uuid-1234']);
    $fake = new FakeDaemonRevocation;
    (new RevokeSftpAccessJob('user-1', $node))->handle();
    $fake->assertDeauthorized('user-1', []);
});
test('job dispatches for individual server', function () {
    $node = Node::factory()->make(['uuid' => 'node-1234']);
    $server = Server::factory()->make(['uuid' => 'server-1234'])->setRelation('node', $node);
    $fake = new FakeDaemonRevocation;
    (new RevokeSftpAccessJob('user-1', $server))->handle();
    $fake->assertDeauthorized('user-1', ['server-1234']);
});
