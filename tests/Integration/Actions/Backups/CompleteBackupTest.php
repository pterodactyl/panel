<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Backups\CompleteBackupTest;

use Aws\MockHandler;
use Aws\Result;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Backups\CompletesBackups;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeBackupManager;
use Pterodactyl\Tests\Support\Fakes\FakeS3Filesystem;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** A backup of the server that Wings is still running. */
function runningBackup(Server $server, array $attributes = []): Backup
{
    return Backup::factory()->create([...['server_id' => $server->id, 'is_successful' => false, 'completed_at' => null], ...$attributes]);
}

test('outcomes reported without the http api meet the same rules', function (array $input, array $fields): void {
    $backup = runningBackup($this->createServerModel());

    try {
        $this->app->make(CompletesBackups::class)->complete($backup, $input);
        $this->fail('The backup was completed.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe($fields);
    }

    expect($backup->fresh()->completed_at)->toBeNull();
})->with([
    'no outcome' => [[], ['successful']],
    'an outcome that is not a boolean' => [['successful' => 'yes', 'checksum' => 'abc', 'checksum_type' => 'sha1', 'size' => 1], ['successful']],
    'a success without its checksum or size' => [['successful' => true], ['checksum', 'checksum_type', 'size']],
    'a size that is not an integer' => [['successful' => true, 'checksum' => 'abc', 'checksum_type' => 'sha1', 'size' => '10.5'], ['size']],
    'parts that are not a list' => [['successful' => false, 'parts' => ['first' => ['etag' => 'a', 'part_number' => 1]]], ['parts']],
    'a part without its number' => [['successful' => false, 'parts' => [['etag' => 'a']]], ['parts.0.part_number']],
]);

test('values are coerced the way laravel validates them', function (bool|int|string $successful): void {
    $backup = runningBackup($this->createServerModel(), ['is_locked' => true]);

    $this->app->make(CompletesBackups::class)->complete($backup, ['successful' => $successful, 'checksum' => 'abc123', 'checksum_type' => 'sha1', 'size' => '1024']);

    $backup->refresh();
    expect($backup->is_successful)->toBeTrue()
        ->and($backup->is_locked)->toBeTrue()
        ->and($backup->checksum)->toBe('sha1:abc123')
        ->and($backup->bytes)->toBe(1024)
        ->and($backup->completed_at)->not->toBeNull();
})->with([true, 1, '1']);

test('a failed backup is unlocked and keeps no checksum or size', function (bool|int|string $successful): void {
    $backup = runningBackup($this->createServerModel(), ['is_locked' => true]);

    $this->app->make(CompletesBackups::class)->complete($backup, ['successful' => $successful, 'checksum' => 'abc123', 'checksum_type' => 'sha1', 'size' => 1024]);

    $backup->refresh();
    expect($backup->is_successful)->toBeFalse()
        ->and($backup->is_locked)->toBeFalse()
        ->and($backup->checksum)->toBeNull()
        ->and($backup->bytes)->toBe(0)
        ->and($backup->completed_at)->not->toBeNull();
    $this->assertActivityOn('server:backup.fail', $backup->server, $backup);
})->with([false, 0, '0']);

test('the parts wings uploaded close the multipart upload, and parts left out are listed from s3', function (): void {
    $filesystem = new FakeS3Filesystem('backups');
    $mock = new MockHandler([new Result([]), new Result(['Parts' => [['ETag' => 'listed', 'PartNumber' => 1]]]), new Result([])]);
    $filesystem->getClient()->getHandlerList()->setHandler($mock);
    $this->app->instance(BackupManager::class, new FakeBackupManager($filesystem));
    $completes = $this->app->make(CompletesBackups::class);

    $completes->complete(runningBackup($this->createServerModel(), ['disk' => Backup::ADAPTER_AWS_S3, 'upload_id' => 'upload-1']), [
        'successful' => true, 'checksum' => 'abc123', 'checksum_type' => 'sha1', 'size' => 1024,
        'parts' => [['etag' => 'first', 'part_number' => '1'], ['etag' => 'second', 'part_number' => 2]],
    ]);

    expect($mock->getLastCommand()->getName())->toBe('CompleteMultipartUpload')
        ->and($mock->getLastCommand()['UploadId'])->toBe('upload-1')
        ->and($mock->getLastCommand()['MultipartUpload']['Parts'])->toBe([['ETag' => 'first', 'PartNumber' => 1], ['ETag' => 'second', 'PartNumber' => 2]]);

    $completes->complete(runningBackup($this->createServerModel(), ['disk' => Backup::ADAPTER_AWS_S3, 'upload_id' => 'upload-2']), ['successful' => true, 'checksum' => 'abc123', 'checksum_type' => 'sha1', 'size' => 1024, 'parts' => null]);

    expect($mock->getLastCommand()->getName())->toBe('CompleteMultipartUpload')
        ->and($mock->getLastCommand()['MultipartUpload']['Parts'])->toBe([['ETag' => 'listed', 'PartNumber' => 1]]);
});
