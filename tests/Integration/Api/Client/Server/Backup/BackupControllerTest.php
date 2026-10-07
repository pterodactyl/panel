<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Backup\BackupControllerTest;

use Pterodactyl\Contracts\Nodes\ResetsServerStates;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonBackup;

uses(ClientApiIntegrationTestCase::class);
test('backups can be listed', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::BackupRead->value]);
    $backups = Backup::factory()->times(2)->create(['server_id' => $server->id]);
    // A backup on another server must never show up in this listing.
    Backup::factory()->create(['server_id' => $this->createServerModel()->id]);
    $response = $this->actingAs($user)->getJson($this->link($server, '/backups'))->assertOk()->assertJsonPath('object', 'list')->assertJsonCount(2, 'data')->assertJsonPath('meta.backup_count', 2)->assertJsonPath('meta.pagination.total', 2);
    $uuids = collect($response->json('data'))->pluck('attributes.uuid');
    expect($uuids->toArray())->toEqualCanonicalizing($backups->pluck('uuid')->toArray());
});
test('backup listing requires read permission', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ControlConsole->value]);
    $this->getJson($this->link($server, '/backups'))->assertUnauthorized();
    $this->actingAs($user)->getJson($this->link($server, '/backups'))->assertForbidden();
});
test('backup can be created', function () {
    $user = User::factory()->create();
    $server = $this->createServerModel(['owner_id' => $user->id, 'backup_limit' => 3]);
    $fake = new FakeDaemonBackup;
    $this->actingAs($user)->postJson($this->link($server, '/backups'), ['name' => 'Test Backup'])->assertOk()->assertJsonPath('object', 'backup')->assertJsonPath('attributes.name', 'Test Backup')->assertJsonPath('attributes.is_locked', false);
    $this->assertDatabaseHas('backups', ['server_id' => $server->id, 'name' => 'Test Backup']);
    $backup = Backup::query()->where('server_id', $server->id)->where('name', 'Test Backup')->firstOrFail();
    $fake->assertBackedUp($backup->uuid);
});
test('backup can be created without a name', function (): void {
    $user = User::factory()->create();
    $server = $this->createServerModel(['owner_id' => $user->id, 'backup_limit' => 3]);
    $fake = new FakeDaemonBackup;
    $response = $this->actingAs($user)->postJson($this->link($server, '/backups'))->assertOk()->assertJsonPath('object', 'backup');
    expect($response->json('attributes.name'))->toStartWith('Backup at ');
    $fake->assertBackedUp($response->json('attributes.uuid'));
});
test('backup cannot be created past the server limit', function () {
    $user = User::factory()->create();
    $server = $this->createServerModel(['owner_id' => $user->id, 'backup_limit' => 1]);
    Backup::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->postJson($this->link($server, '/backups'), ['name' => 'One Too Many'])->assertBadRequest();
    $this->assertDatabaseMissing('backups', ['server_id' => $server->id, 'name' => 'One Too Many']);
});
test('backup lock can be toggled', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::BackupDelete->value]);
    $backup = Backup::factory()->create(['server_id' => $server->id, 'is_locked' => false]);
    $this->actingAs($user)->postJson($this->link($backup, '/lock'))->assertOk()->assertJsonPath('attributes.is_locked', true);
    expect($backup->refresh()->is_locked)->toBeTrue();
    $this->postJson($this->link($backup, '/lock'))->assertOk()->assertJsonPath('attributes.is_locked', false);
    expect($backup->refresh()->is_locked)->toBeFalse();
});
test('lock toggle requires delete permission', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::BackupCreate->value]);
    $backup = Backup::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->postJson($this->link($backup, '/lock'))->assertForbidden();
});
test('backup can be restored', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::BackupRestore->value]);
    $backup = Backup::factory()->create(['server_id' => $server->id, 'completed_at' => now(), 'is_successful' => true]);
    $fake = new FakeDaemonBackup;
    $this->actingAs($user)->postJson($this->link($backup, '/restore'), ['truncate' => false])->assertNoContent();
    // The server is placed into a restoring state while Wings works.
    expect($server->refresh()->status)->toBe('restoring_backup');
    $fake->assertRestored($backup->uuid);
});
test('a restore interrupted by a wings restart is logged as failed', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::BackupRestore->value]);
    $backup = Backup::factory()->create(['server_id' => $server->id, 'completed_at' => now(), 'is_successful' => true]);
    new FakeDaemonBackup;
    $this->actingAs($user)->postJson($this->link($backup, '/restore'), ['truncate' => false])->assertNoContent();

    $this->app->make(ResetsServerStates::class)->reset($server->node);

    expect($server->refresh()->status)->toBeNull();
    expect($server->activity()->where('event', 'server:backup.restore-failed')->exists())->toBeTrue();
});
test('backup cannot be restored while server is busy', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::BackupRestore->value]);
    $server->update(['status' => 'installing']);
    $backup = Backup::factory()->create(['server_id' => $server->id, 'completed_at' => now(), 'is_successful' => true]);
    // The server-state middleware rejects requests against a server that is
    // still installing before the controller's own status check runs.
    $this->actingAs($user)->postJson($this->link($backup, '/restore'), ['truncate' => false])->assertConflict();
});
test('a backup can only be restored once it completed successfully', function (bool $isSuccessful, bool $isCompleted): void {
    [$user, $server] = $this->generateTestAccount([Permissions::BackupRestore->value]);
    $backup = Backup::factory()->create(['server_id' => $server->id, 'completed_at' => $isCompleted ? now() : null, 'is_successful' => $isSuccessful]);
    $fake = new FakeDaemonBackup;
    $this->actingAs($user)->postJson($this->link($backup, '/restore'), ['truncate' => true])->assertBadRequest();
    expect($server->refresh()->status)->toBeNull();
    expect(collect($fake->calls)->where('method', 'restore'))->toBeEmpty();
})->with([
    'failed but completed' => [false, true],
    'failed and incomplete' => [false, false],
    'successful but incomplete' => [true, false],
]);
test('restore requires truncate flag', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::BackupRestore->value]);
    $backup = Backup::factory()->create(['server_id' => $server->id, 'completed_at' => now(), 'is_successful' => true]);
    $this->actingAs($user)->postJson($this->link($backup, '/restore'))->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'truncate');
});
