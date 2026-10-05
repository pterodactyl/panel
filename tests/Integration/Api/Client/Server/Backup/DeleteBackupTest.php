<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Backup\DeleteBackupTest;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Events\ActivityLogged;
use Pterodactyl\Models\Backup;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonBackup;

uses(ClientApiIntegrationTestCase::class);
beforeEach(function () {
    $this->repository = new FakeDaemonBackup;
});
test('user without permission cannot delete backup', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::BackupCreate->value]);
    $backup = Backup::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->deleteJson($this->link($backup))->assertStatus(Response::HTTP_FORBIDDEN);
});
test('backup can be deleted', function () {
    Event::fake([ActivityLogged::class]);
    [$user, $server] = $this->generateTestAccount([Permissions::BackupDelete->value]);
    /** @var Backup $backup */
    $backup = Backup::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->deleteJson($this->link($backup))->assertStatus(Response::HTTP_NO_CONTENT);
    $backup->refresh();
    $this->assertSoftDeleted($backup);
    $this->repository->assertDeleted($backup->uuid);
    $this->assertActivityFor('server:backup.delete', $user, [$backup, $backup->server]);
    $this->actingAs($user)->deleteJson($this->link($backup))->assertStatus(Response::HTTP_NOT_FOUND);
});
