<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Daemon\DaemonClientTest;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

function node(string $host = 'wings.example.test', string $token = 'token-one'): Node
{
    return Node::factory()->make([
        'location_id' => 1,
        'fqdn' => $host,
        'scheme' => 'https',
        'daemonListen' => 8080,
        'daemon_token' => Crypt::encrypt($token),
    ])->setRelation('mounts', new Collection);
}

function server(Node $node, string $uuid = 'server-one'): Server
{
    return Server::factory()->make([
        'uuid' => $uuid,
        'owner_id' => 1,
        'node_id' => 1,
        'egg_id' => 1,
        'allocation_id' => 1,
    ])->setRelation('node', $node);
}

test('retained clients keep their server and credentials across interleaved operations', function (): void {
    $firstNode = node('first.example.test', 'first-token');
    $secondNode = node('second.example.test', 'second-token');
    $firstServer = server($firstNode, 'first-server');
    $first = Daemon::server($firstServer);
    $second = Daemon::server(server($secondNode, 'second-server'));
    Http::fake([
        'https://first.example.test:8080/api/*' => Http::response(),
        'https://second.example.test:8080/api/*' => Http::response(),
    ]);
    $firstServer->uuid = 'changed-server';
    $firstNode->fqdn = 'changed.example.test';
    $firstNode->daemon_token = Crypt::encrypt('changed-token');

    $first->sync();
    $second->power('restart');
    $first->files()->putContent('/file.txt', 'first');
    $first->backups()->delete(Backup::factory()->make(['server_id' => 1, 'uuid' => 'backup-one']));

    expect(Http::recorded()->map(fn (array $pair): array => [
        $pair[0]->method(), $pair[0]->url(), $pair[0]->header('Authorization')[0],
    ])->all())->toBe([
        ['POST', 'https://first.example.test:8080/api/servers/first-server/sync', 'Bearer first-token'],
        ['POST', 'https://second.example.test:8080/api/servers/second-server/power', 'Bearer second-token'],
        ['POST', 'https://first.example.test:8080/api/servers/first-server/files/write?file=%2Ffile.txt', 'Bearer first-token'],
        ['DELETE', 'https://first.example.test:8080/api/servers/first-server/backup/backup-one', 'Bearer first-token'],
    ]);
});

test('explicit source nodes override the server destination for transfer cleanup', function (): void {
    $source = node('source.example.test', 'source-token');
    $destination = node('destination.example.test', 'destination-token');
    $server = server($destination);
    Http::fake(['https://source.example.test:8080/api/servers/server-one' => Http::response()]);

    Daemon::server($server, $source)->delete();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://source.example.test:8080/api/servers/server-one'
        && $request->hasHeader('Authorization', 'Bearer source-token'));
    Http::assertSentCount(1);
});

test('node configuration updates use selected credentials and the new configuration', function (): void {
    $original = node();
    $client = Daemon::node($original);
    $original->daemon_token = Crypt::encrypt('new-token');
    $original->daemonListen = 9090;
    Http::fake(['https://wings.example.test:8080/api/update' => Http::response()]);

    $client->update($original);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'Bearer token-one')
        && $request['token'] === 'new-token'
        && $request['api']['port'] === 9090);
});

test('transport applies configured timeouts and the existing TLS policy', function (string $environment, bool $verify): void {
    $this->app->detectEnvironment(fn (): string => $environment);
    config()->set(['pterodactyl.guzzle.timeout' => '17', 'pterodactyl.guzzle.connect_timeout' => '4']);
    $options = [];
    Http::fake(['https://wings.example.test:8080/api/servers/server-one/sync' => function (Request $request, array $requestOptions) use (&$options) {
        $options = $requestOptions;

        return Http::response();
    }]);

    Daemon::server(server(node()))->sync();

    expect($options)->toMatchArray(['timeout' => 17, 'connect_timeout' => 4, 'verify' => $verify]);
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Accept', 'application/json')
        && $request->hasHeader('Content-Type', 'application/json'));
})->with([['production', true], ['testing', false]]);

test('Wings HTTP errors retain status and request context without retrying', function (int $status): void {
    Http::fake(['https://wings.example.test:8080/api/servers/server-one/sync' => Http::response(['error' => 'remote detail'], $status, ['X-Request-Id' => 'request-one'])]);

    try {
        Daemon::server(server(node()))->sync();
        $this->fail('Expected a Wings error.');
    } catch (DaemonConnectionException $exception) {
        expect($exception->getStatusCode())->toBe($status)
            ->and($exception->getRequestId())->toBe('request-one')
            ->and($exception->getPrevious())->toBeInstanceOf(RequestException::class);
        expect(str_contains($exception->getMessage(), 'remote detail'))->toBe($status < 500);
    }

    Http::assertSentCount(1);
})->with([400, 404, 422, 500, 502, 504]);

test('connection failures use the gateway timeout contract', function (): void {
    Http::fake(['https://wings.example.test:8080/api/servers/server-one/sync' => Http::failedConnection()]);

    try {
        Daemon::server(server(node()))->sync();
        $this->fail('Expected a connection error.');
    } catch (DaemonConnectionException $exception) {
        expect($exception->getStatusCode())->toBe(504)
            ->and($exception->getRequestId())->toBeNull()
            ->and($exception->getPrevious())->toBeInstanceOf(ConnectionException::class);
    }
});

test('resource details retain the gateway timeout status for upstream errors', function (): void {
    Http::fake(['https://wings.example.test:8080/api/servers/server-one' => Http::response([], 404, ['X-Request-Id' => 'resource-request'])]);

    try {
        Daemon::server(server(node()))->details();
        $this->fail('Expected a resource error.');
    } catch (DaemonConnectionException $exception) {
        expect($exception->getStatusCode())->toBe(504)
            ->and($exception->getRequestId())->toBe('resource-request');
    }
});

test('file writes preserve raw content and encode the file query', function (): void {
    $url = 'https://wings.example.test:8080/api/servers/server-one/files/write?file=%2Fhello%20world.txt';
    Http::fake([$url => Http::response()]);
    $content = "line one\n{\"raw\": true}\n";

    Daemon::server(server(node()))->files()->putContent('/hello world.txt', $content);

    Http::assertSent(fn (Request $request): bool => $request->url() === $url && $request->body() === $content);
});

test('file content limits reject oversized files', function (): void {
    Http::fake(['https://wings.example.test:8080/api/servers/server-one/files/contents*' => Http::response('contents', 200, ['Content-Length' => 8])]);

    expect(fn (): string => Daemon::server(server(node()))->files()->getContent('/file.txt', 7))->toThrow(FileSizeTooLargeException::class);
    expect(Daemon::server(server(node()))->files()->getContent('/file.txt', 8))->toBe('contents');
});

test('archive timeouts and foreground pulls stay local to each request', function (): void {
    $timeouts = [];
    Http::fake(['https://wings.example.test:8080/api/servers/server-one/files/*' => function (Request $request, array $options) use (&$timeouts) {
        $timeouts[] = $options['timeout'];

        return Http::response([]);
    }]);
    $files = Daemon::server(server(node()))->files();

    $files->compressFiles('/', ['file.txt']);
    $files->decompressFile('/', 'archive.tar.gz');
    $files->pull('https://downloads.example.test/archive', null, ['filename' => 'archive.zip', 'foreground' => true, 'use_header' => false, 'timeout' => 1200]);
    $files->pull('https://downloads.example.test/other', null);

    expect($timeouts)->toBe([900, 900, 1200, 15]);
    Http::assertSent(fn (Request $request): bool => ($request->data()['url'] ?? null) === 'https://downloads.example.test/archive'
        && $request->data() === ['url' => 'https://downloads.example.test/archive', 'root' => '/', 'file_name' => 'archive.zip', 'use_header' => false, 'foreground' => true]);
    Http::assertSent(fn (Request $request): bool => ($request->data()['url'] ?? null) === 'https://downloads.example.test/other'
        && $request->data() === ['url' => 'https://downloads.example.test/other', 'root' => '/']);
});

test('backup adapters are chosen per backup and restore parameters are preserved', function (): void {
    Http::fake(['https://wings.example.test:8080/api/servers/server-one/backup*' => Http::response()]);
    $backup = Backup::factory()->make(['server_id' => 1, 'uuid' => 'backup-one', 'disk' => 'wings', 'ignored_files' => ['cache', '*.log']]);
    $backups = Daemon::server(server(node()))->backups();

    $backups->create($backup, 's3');
    $backups->create($backup);
    $backups->restore($backup, 'https://download.example.test/backup', true);

    expect(Http::recorded()->map(fn (array $pair): array => $pair[0]->data())->all())->toBe([
        ['adapter' => 's3', 'uuid' => 'backup-one', 'ignore' => "cache\n*.log"],
        ['adapter' => 'wings', 'uuid' => 'backup-one', 'ignore' => "cache\n*.log"],
        ['adapter' => 'wings', 'truncate_directory' => true, 'download_url' => 'https://download.example.test/backup'],
    ]);
});

test('commands preserve batch payloads and deauthorization preserves server scope', function (): void {
    $node = node();
    Http::fake([
        'https://wings.example.test:8080/api/servers/server-one/commands' => Http::response(),
        'https://wings.example.test:8080/api/deauthorize-user' => Http::response(),
    ]);

    Daemon::server(server($node))->commands(['say first', 'say second']);
    Daemon::node($node)->deauthorize('user-one', ['server-one']);

    Http::assertSent(fn (Request $request): bool => ($request->data()['commands'] ?? null) === ['say first', 'say second']);
    Http::assertSent(fn (Request $request): bool => ($request->data()['user'] ?? null) === 'user-one' && ($request->data()['servers'] ?? null) === ['server-one']);
});
