<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Files\FileControllerTest;

use Illuminate\Support\Facades\Event;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Events\ActivityLogged;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonFile;

uses(ClientApiIntegrationTestCase::class);
const FILE_CONTROLLER_FILE_OBJECT = ['name' => 'test.txt', 'mode' => '-rw-r--r--', 'mode_bits' => '0644', 'size' => 100, 'file' => true, 'symlink' => false, 'mime' => 'text/plain', 'created' => '2024-01-01T00:00:00Z', 'modified' => '2024-01-01T00:00:00Z'];
dataset('endpointsDataProvider', fn (): array => ['list' => ['getJson', '/files/list'], 'contents' => ['getJson', '/files/contents?file=/test.txt'], 'download' => ['getJson', '/files/download?file=/test.txt'], 'upload' => ['getJson', '/files/upload'], 'write' => ['postJson', '/files/write?file=/test.txt'], 'create-folder' => ['postJson', '/files/create-folder'], 'rename' => ['putJson', '/files/rename'], 'copy' => ['postJson', '/files/copy'], 'decompress' => ['postJson', '/files/decompress'], 'delete' => ['postJson', '/files/delete'], 'chmod' => ['postJson', '/files/chmod'], 'pull' => ['postJson', '/files/pull']]);
test('endpoints require authentication and permission', function (string $method, string $path): void {
    [, $server] = $this->generateTestAccount();
    $this->{$method}($this->link($server, $path))->assertUnauthorized();
    [$user, $server] = $this->generateTestAccount([Permissions::ControlConsole->value]);
    $this->actingAs($user)->{$method}($this->link($server, $path))->assertForbidden();
})->with('endpointsDataProvider');
test('directory listing is returned', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileRead->value]);
    $fake = new FakeDaemonFile;
    $fake->directory = [FILE_CONTROLLER_FILE_OBJECT];
    $this->actingAs($user)->getJson($this->link($server, '/files/list'))->assertOk()->assertJsonPath('object', 'list')->assertJsonPath('data.0.object', 'file_object')->assertJsonPath('data.0.attributes.name', 'test.txt')->assertJsonPath('data.0.attributes.is_file', true)->assertJsonPath('data.0.attributes.mimetype', 'text/plain');
    $fake->assertDirectoryListed('/');
});
test('directory query parameter is passed to wings', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileRead->value]);
    $fake = new FakeDaemonFile;
    $fake->directory = [];
    $this->actingAs($user)->getJson($this->link($server, '/files/list?directory=%2Fnested%2Fpath'))->assertOk()->assertJsonPath('data', []);
    $fake->assertDirectoryListed('/nested/path');
});
test('file contents are returned as text', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileReadContent->value]);
    $fake = new FakeDaemonFile;
    $fake->content = 'file contents here';

    $response = $this->actingAs($user)->get($this->link($server, '/files/contents?file=%2Ftest.txt'));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');

    expect($response->getContent())->toBe('file contents here');
    $fake->assertContentFetched('/test.txt');
});
test('reading file contents records the file path in the activity log', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileReadContent->value]);
    $fake = new FakeDaemonFile;
    $fake->content = 'file contents here';
    $this->actingAs($user)->get($this->link($server, '/files/contents?file=%2Fconfig%2Fserver.properties'))->assertOk();
    $fake->assertContentFetched('/config/server.properties');
    Event::assertDispatched(ActivityLogged::class, fn (ActivityLogged $event): bool => $event->is('server:file.read') && $event->model->propertyValues() === ['file' => '/config/server.properties']);
});
test('file contents requires file parameter', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileReadContent->value]);
    $this->actingAs($user)->getJson($this->link($server, '/files/contents'))->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'file');
});
test('download url is signed for the server node', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileReadContent->value]);
    $response = $this->actingAs($user)->getJson($this->link($server, '/files/download?file=%2Ftest.txt'))->assertOk()->assertJsonPath('object', 'signed_url');
    $url = $response->json('attributes.url');
    expect($url)->toStartWith($server->node->getConnectionAddress().'/download/file?token=');
});
test('upload url is signed for the server node', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileCreate->value, Permissions::FileUpdate->value]);
    $response = $this->actingAs($user)->getJson($this->link($server, '/files/upload'))->assertOk()->assertJsonPath('object', 'signed_url');
    $url = $response->json('attributes.url');
    expect($url)->toStartWith($server->node->getConnectionAddress().'/upload/file?token=');
});
test('upload url requires both create and update permission', function (Permissions $permission): void {
    [$user, $server] = $this->generateTestAccount([$permission->value]);
    $this->actingAs($user)->getJson($this->link($server, '/files/upload'))->assertForbidden();
})->with(['create only' => Permissions::FileCreate, 'update only' => Permissions::FileUpdate]);
test('file contents can be written', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileCreate->value]);
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->call('POST', $this->link($server, '/files/write?file=%2Ftest.txt'), [], [], [], $this->transformHeadersToServerVars(['Accept' => 'application/json']), 'new file contents')->assertNoContent();
    $fake->assertDirectoryListed('/');
    $fake->assertContentPut('/test.txt', 'new file contents');
});
test('new file can be written into a directory that does not exist yet', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileCreate->value]);
    $fake = new FakeDaemonFile;
    $fake->directoryMissing = true;
    $this->actingAs($user)->call('POST', $this->link($server, '/files/write?file=%2Fnew%2Ftest.txt'), [], [], [], $this->transformHeadersToServerVars(['Accept' => 'application/json']), 'new file contents')->assertNoContent();
    $fake->assertDirectoryListed('/new');
    $fake->assertContentPut('/new/test.txt', 'new file contents');
});
test('existing file cannot be overwritten without update permission', function (string $path): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileCreate->value]);
    $fake = new FakeDaemonFile;
    $fake->directory = [FILE_CONTROLLER_FILE_OBJECT];
    $this->actingAs($user)->call('POST', $this->link($server, '/files/write?file='.rawurlencode($path)), [], [], [], $this->transformHeadersToServerVars(['Accept' => 'application/json']), 'overwritten')->assertForbidden();
    expect($fake->callsFor('putContent'))->toBeEmpty();
})->with(['plain' => '/test.txt', 'relative' => 'test.txt', 'dot segments' => '/nested/../test.txt', 'trailing dot' => '/test.txt/.', 'duplicate slashes' => '//test.txt']);
test('existing file can be overwritten with update permission', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileUpdate->value]);
    $fake = new FakeDaemonFile;
    $fake->directory = [FILE_CONTROLLER_FILE_OBJECT];
    $this->actingAs($user)->call('POST', $this->link($server, '/files/write?file=%2Ftest.txt'), [], [], [], $this->transformHeadersToServerVars(['Accept' => 'application/json']), 'updated contents')->assertNoContent();
    $fake->assertContentPut('/test.txt', 'updated contents');
});
test('new file cannot be created with only update permission', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileUpdate->value]);
    $fake = new FakeDaemonFile;
    $fake->directory = [FILE_CONTROLLER_FILE_OBJECT];
    $this->actingAs($user)->call('POST', $this->link($server, '/files/write?file=%2Fother.txt'), [], [], [], $this->transformHeadersToServerVars(['Accept' => 'application/json']), 'new file contents')->assertForbidden();
    expect($fake->callsFor('putContent'))->toBeEmpty();
});
test('folder can be created', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileCreate->value]);
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->postJson($this->link($server, '/files/create-folder'), ['root' => '/', 'name' => 'new-folder'])->assertNoContent();
    $fake->assertDirectoryCreated('new-folder', '/');
});
test('files can be renamed', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileUpdate->value]);
    $files = [['from' => 'old.txt', 'to' => 'new.txt']];
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->putJson($this->link($server, '/files/rename'), ['root' => '/', 'files' => $files])->assertNoContent();
    $fake->assertFilesRenamed('/', $files);
});
test('file can be copied', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileCreate->value]);
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->postJson($this->link($server, '/files/copy'), ['location' => '/test.txt'])->assertNoContent();
    $fake->assertFileCopied('/test.txt');
});
test('file can be decompressed', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileCreate->value, Permissions::FileUpdate->value]);
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->postJson($this->link($server, '/files/decompress'), ['root' => '/', 'file' => 'archive.tar.gz'])->assertNoContent();
    $fake->assertFileDecompressed('/', 'archive.tar.gz');
});
test('decompress requires both create and update permission', function (Permissions $permission): void {
    [$user, $server] = $this->generateTestAccount([$permission->value]);
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->postJson($this->link($server, '/files/decompress'), ['root' => '/', 'file' => 'archive.tar.gz'])->assertForbidden();
    expect($fake->callsFor('decompressFile'))->toBeEmpty();
})->with(['create only' => Permissions::FileCreate, 'update only' => Permissions::FileUpdate]);
test('files can be deleted', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileDelete->value]);
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->postJson($this->link($server, '/files/delete'), ['root' => '/', 'files' => ['test.txt']])->assertNoContent();
    $fake->assertFilesDeleted('/', ['test.txt']);
});
test('file permissions can be updated', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileUpdate->value]);
    $files = [['file' => 'test.txt', 'mode' => '0644']];
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->postJson($this->link($server, '/files/chmod'), ['root' => '/', 'files' => $files])->assertNoContent();
    $fake->assertFilesChmoded('/', $files);
});
test('file permissions require an octal mode string', function (mixed $mode): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileUpdate->value]);
    $this->actingAs($user)->postJson($this->link($server, '/files/chmod'), ['root' => '/', 'files' => [['file' => 'test.txt', 'mode' => $mode]]])->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'files.0.mode');
})->with(['integer' => 420, 'non octal digit' => '0648', 'too short' => '64', 'too long' => '00644', 'symbolic' => 'rw-r--r--']);
test('remote file can be pulled', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileCreate->value, Permissions::FileUpdate->value]);
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->postJson($this->link($server, '/files/pull'), ['url' => 'https://cdn.example.com/file.zip', 'directory' => '/'])->assertNoContent();
    $fake->assertPulled('https://cdn.example.com/file.zip');
});
test('pull requires both create and update permission', function (Permissions $permission, array $payload): void {
    [$user, $server] = $this->generateTestAccount([$permission->value]);
    $fake = new FakeDaemonFile;
    $this->actingAs($user)->postJson($this->link($server, '/files/pull'), ['url' => 'https://cdn.example.com/file.zip', 'directory' => '/', ...$payload])->assertForbidden();
    expect($fake->callsFor('pull'))->toBeEmpty();
})->with(['create only' => Permissions::FileCreate, 'update only' => Permissions::FileUpdate])->with(['explicit filename' => [['filename' => 'server.properties']], 'header filename' => [['use_header' => true]], 'url filename' => [[]]]);
test('pull requires a valid url', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::FileCreate->value, Permissions::FileUpdate->value]);
    $this->actingAs($user)->postJson($this->link($server, '/files/pull'), ['url' => 'not-a-url'])->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'url');
});
