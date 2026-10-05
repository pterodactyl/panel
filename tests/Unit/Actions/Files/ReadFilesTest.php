<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Actions\Files\ReadFilesTest;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Actions\Files\ListDirectory;
use Pterodactyl\Actions\Files\ReadFileContents;
use Pterodactyl\Contracts\Files\ListsDirectories;
use Pterodactyl\Contracts\Files\ReadsFileContents;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonFile;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);

function server(): Server
{
    $server = Server::factory()->make(['owner_id' => 1, 'node_id' => 1, 'egg_id' => 1, 'allocation_id' => 1]);

    return $server->setRelation('node', Node::factory()->make(['location_id' => 1]));
}

test('file actions resolve from their contracts', function (): void {
    expect($this->app->make(ReadsFileContents::class))->toBeInstanceOf(ReadFileContents::class)
        ->and($this->app->make(ListsDirectories::class))->toBeInstanceOf(ListDirectory::class);
});

test('file contents are read from Wings', function (): void {
    $fake = new FakeDaemonFile;
    $fake->content = "motd=Hello\n";

    expect($this->app->make(ReadsFileContents::class)->read(server(), '/server.properties'))->toBe("motd=Hello\n");
    $fake->assertContentFetched('/server.properties');
});

test('reads are bounded by the configured edit size unless a limit is given', function (?int $limit, bool $rejected): void {
    config()->set('pterodactyl.files.max_edit_size', 8);
    Http::fake(['*/files/contents*' => Http::response('123456', 200, ['Content-Length' => '6'])]);

    $read = fn (): string => $this->app->make(ReadsFileContents::class)->read(server(), '/large.log', $limit);

    $rejected
        ? expect($read)->toThrow(FileSizeTooLargeException::class)
        : expect($read())->toBe('123456');
})->with([
    'default limit allows the file' => [null, false],
    'explicit limit equal to the size allows the file' => [6, false],
    'explicit lower limit rejects the file' => [5, true],
]);

test('the configured edit size rejects larger files by default', function (): void {
    config()->set('pterodactyl.files.max_edit_size', 4);
    Http::fake(['*/files/contents*' => Http::response('123456', 200, ['Content-Length' => '6'])]);

    $this->app->make(ReadsFileContents::class)->read(server(), '/large.log');
})->throws(FileSizeTooLargeException::class);

test('directories are listed from Wings', function (): void {
    $entry = ['name' => 'eula.txt', 'mode' => '-rw-r--r--', 'mode_bits' => '0644', 'size' => 9, 'file' => true, 'symlink' => false, 'mime' => 'text/plain', 'created' => '2024-01-01T00:00:00Z', 'modified' => '2024-01-01T00:00:00Z'];
    $fake = new FakeDaemonFile;
    $fake->directory = [$entry];

    expect($this->app->make(ListsDirectories::class)->list(server(), '/config'))->toBe([$entry]);
    $fake->assertDirectoryListed('/config');
});

test('directory listings default to the server root', function (): void {
    $fake = new FakeDaemonFile;

    expect($this->app->make(ListsDirectories::class)->list(server()))->toBe([]);
    $fake->assertDirectoryListed('/');
});

test('Wings failures surface as daemon connection exceptions', function (): void {
    Http::fake(fn (Request $request) => Http::response(['error' => 'missing'], 404));

    expect(fn (): string => $this->app->make(ReadsFileContents::class)->read(server(), '/missing.txt'))
        ->toThrow(fn (DaemonConnectionException $exception) => expect($exception->getStatusCode())->toBe(404));
    expect(fn (): array => $this->app->make(ListsDirectories::class)->list(server(), '/missing'))
        ->toThrow(DaemonConnectionException::class);
});
