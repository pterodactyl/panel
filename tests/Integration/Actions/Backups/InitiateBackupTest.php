<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Backups\InitiateBackupTest;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Contracts\Backups\InitiatesBackups;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;
use Pterodactyl\Models\Backup;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonBackup;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    config()->set('backups.throttles.period', 0);
});

test('a server at its limit rotates out the oldest backup once wings accepts the new one', function (): void {
    $server = $this->createServerModel(['backup_limit' => 2]);
    $oldest = Backup::factory()->create(['server_id' => $server->id, 'created_at' => CarbonImmutable::now()->subDays(2)]);
    $newer = Backup::factory()->create(['server_id' => $server->id, 'created_at' => CarbonImmutable::now()->subDay()]);
    $fake = new FakeDaemonBackup;

    $backup = $this->app->make(InitiatesBackups::class)->initiate($server, 'Rotated', true);

    $fake->assertBackedUp($backup->uuid);
    $fake->assertDeleted($oldest->uuid);
    $this->assertSoftDeleted($oldest);
    $this->assertNotSoftDeleted($newer);
    expect($backup->completed_at)->toBeNull();
});

test('a backup wings rejects is marked failed and the oldest backup is kept', function (): void {
    $server = $this->createServerModel(['backup_limit' => 1]);
    $oldest = Backup::factory()->create(['server_id' => $server->id, 'created_at' => CarbonImmutable::now()->subDay()]);
    $fake = new FakeDaemonBackup;
    $fake->throwable = new DaemonConnectionException(Http::failedRequest([], 500));

    try {
        $this->app->make(InitiatesBackups::class)->setIsLocked(true)->initiate($server, 'Rejected', true);
        $this->fail('Expected DaemonConnectionException to be thrown.');
    } catch (DaemonConnectionException) {
    }

    expect($fake->callsFor('delete'))->toBeEmpty();
    $this->assertNotSoftDeleted($oldest);
    $failed = Backup::query()->where('server_id', $server->id)->where('name', 'Rejected')->firstOrFail();
    expect($failed->is_successful)->toBeFalse()
        ->and($failed->is_locked)->toBeFalse()
        ->and($failed->completed_at)->not->toBeNull();
});

test('a backup rotated out by another request that is still pending is not counted twice', function (): void {
    // Two backups against a limit of two plus one more: the oldest was picked for rotation by an
    // earlier request whose delete has not run yet. This request has to rotate the next one too.
    $server = $this->createServerModel(['backup_limit' => 2]);
    $first = Backup::factory()->create(['server_id' => $server->id, 'created_at' => CarbonImmutable::now()->subDays(3)]);
    $second = Backup::factory()->create(['server_id' => $server->id, 'created_at' => CarbonImmutable::now()->subDays(2)]);
    $third = Backup::factory()->create(['server_id' => $server->id, 'created_at' => CarbonImmutable::now()->subDay()]);
    $fake = new FakeDaemonBackup;

    $backup = $this->app->make(InitiatesBackups::class)->initiate($server, 'Next', true);

    $fake->assertBackedUp($backup->uuid);
    $this->assertSoftDeleted($first);
    $this->assertSoftDeleted($second);
    $this->assertNotSoftDeleted($third);
    expect(Backup::query()->where('server_id', $server->id)->count())->toBe(2);
});

test('a server at its limit without an unlocked backup is rejected before wings is called', function (): void {
    $server = $this->createServerModel(['backup_limit' => 1]);
    Backup::factory()->create(['server_id' => $server->id, 'is_locked' => true]);
    $fake = new FakeDaemonBackup;

    expect(fn () => $this->app->make(InitiatesBackups::class)->initiate($server, 'Blocked', true))
        ->toThrow(TooManyBackupsException::class);

    $fake->assertNothingHappened();
    $this->assertDatabaseMissing('backups', ['server_id' => $server->id, 'name' => 'Blocked']);
});
