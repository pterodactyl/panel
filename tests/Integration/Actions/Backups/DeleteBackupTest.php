<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Backups\DeleteBackupTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Contracts\Backups\DeletesBackups;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Backup\BackupLockedException;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Models\Backup;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeBackupManager;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonBackup;
use Pterodactyl\Tests\Support\Fakes\FakeS3Filesystem;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
test('locked backup cannot be deleted', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $server->id, 'is_locked' => true]);
    try {
        $this->app->make(DeletesBackups::class)->delete($backup);
        $this->fail('Expected BackupLockedException to be thrown.');
    } catch (BackupLockedException) {
    }
    $this->assertModelExists($backup);
});
test('failed backup that is locked can be deleted', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $server->id, 'is_locked' => true, 'is_successful' => false]);
    $fake = new FakeDaemonBackup;
    $this->app->make(DeletesBackups::class)->delete($backup);
    $backup->refresh();
    expect($backup->deleted_at)->not->toBeNull();
    $fake->assertDeleted($backup->uuid);
});
test('exception thrown due to missing backup is ignored', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $server->id]);
    $fake = new FakeDaemonBackup;
    $fake->throwable = new DaemonConnectionException(Http::failedRequest([], 404));
    $this->app->make(DeletesBackups::class)->delete($backup);
    $backup->refresh();
    expect($backup->deleted_at)->not->toBeNull();
    $fake->assertDeleted($backup->uuid);
});
test('exception is thrown if not404', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['server_id' => $server->id]);
    $fake = new FakeDaemonBackup;
    $fake->throwable = new DaemonConnectionException(Http::failedRequest([], 500));
    try {
        $this->app->make(DeletesBackups::class)->delete($backup);
        $this->fail('Expected DaemonConnectionException to be thrown.');
    } catch (DaemonConnectionException) {
    }
    $backup->refresh();
    expect($backup->deleted_at)->toBeNull();
    $fake->assertDeleted($backup->uuid);
});
test('s3 object can be deleted', function () {
    $server = $this->createServerModel();
    $backup = Backup::factory()->create(['disk' => Backup::ADAPTER_AWS_S3, 'server_id' => $server->id]);
    $filesystem = new FakeS3Filesystem('foobar');
    $manager = new FakeBackupManager($filesystem);
    $this->app->instance(BackupManager::class, $manager);
    $this->app->make(DeletesBackups::class)->delete($backup);
    $filesystem->assertDeleted(sprintf('%s/%s.tar.gz', $server->uuid, $backup->uuid));
    $this->assertSoftDeleted($backup);
});
