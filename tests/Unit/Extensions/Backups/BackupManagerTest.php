<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Extensions\Backups\BackupManagerTest;

use InvalidArgumentException;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('resolves custom creators by adapter name', function () {
    config()->set('backups.disks.archive', ['adapter' => 'custom']);
    $adapter = new InMemoryFilesystemAdapter();
    $manager = (new BackupManager($this->app))->extend('custom', fn (): InMemoryFilesystemAdapter => $adapter);
    expect($manager->adapter('archive'))->toBe($adapter);
});
test('rejects s3 configuration without a bucket', function () {
    config()->set('backups.disks.invalid-s3', ['adapter' => 's3']);
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The S3 backup adapter requires a bucket.');
    (new BackupManager($this->app))->adapter('invalid-s3');
});
