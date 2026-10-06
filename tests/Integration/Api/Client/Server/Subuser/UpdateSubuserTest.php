<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Subuser\UpdateSubuserTest;

use Illuminate\Support\Facades\Bus;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
test('correct permissions are required for updating', function () {
    Bus::fake([RevokeSftpAccessJob::class]);
    [$user, $server] = $this->generateTestAccount(['user.read']);
    $subuser = Subuser::factory()->for(User::factory()->create())->for($server)->create(['permissions' => ['control.start']]);
    $this->postJson($endpoint = "/api/client/servers/{$server->uuid}/users/{$subuser->user->uuid}", $data = ['permissions' => ['control.start', 'control.stop']])->assertUnauthorized();
    $this->actingAs($subuser->user)->postJson($endpoint, $data)->assertForbidden();
    $this->actingAs($user)->postJson($endpoint, $data)->assertForbidden();
    $server->subusers()->where('user_id', $user->id)->update(['permissions' => [Permissions::UserUpdate->value, Permissions::ControlStart->value, Permissions::ControlStop->value]]);
    $this->postJson($endpoint, $data)->assertOk();
    Bus::assertDispatchedTimes(function (RevokeSftpAccessJob $job) use ($server, $subuser) {
        return $job->user === $subuser->user->uuid && $job->target->is($server);
    });
});
test('permissions are saved to account', function () {
    Bus::fake([RevokeSftpAccessJob::class]);
    [$user, $server] = $this->generateTestAccount();
    /** @var Subuser $subuser */
    $subuser = Subuser::factory()->for(User::factory()->create())->for($server)->create(['permissions' => ['control.restart', 'websocket.connect', 'foo.bar']]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/users/{$subuser->user->uuid}", ['permissions' => ['control.start', 'control.stop', 'control.stop', 'foo.bar', 'power.fake']])->assertOk();
    $subuser->refresh();
    expect($subuser->permissions)->toEqualCanonicalizing(['control.start', 'control.stop', 'websocket.connect']);
    Bus::assertDispatchedTimes(function (RevokeSftpAccessJob $job) use ($server, $subuser) {
        return $job->user === $subuser->user->uuid && $job->target->is($server);
    });
});
test('user cannot assign permissions they do not have', function () {
    Bus::fake([RevokeSftpAccessJob::class]);
    [$user, $server] = $this->generateTestAccount([Permissions::UserRead->value, Permissions::UserUpdate->value]);
    $subuser = Subuser::factory()->for(User::factory()->create())->for($server)->create(['permissions' => ['foo.bar']]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/users/{$subuser->user->uuid}", ['permissions' => [Permissions::UserRead->value, Permissions::ControlConsole->value]])->assertForbidden();
    expect($subuser->refresh()->permissions)->toEqualCanonicalizing(['foo.bar']);
    Bus::assertNothingDispatched();
});
test('user cannot update self', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::UserRead->value, Permissions::UserUpdate->value]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/users/{$user->uuid}", [])->assertForbidden();
});
test('cannot update subuser for different server', function () {
    [$user, $server] = $this->generateTestAccount();
    [$user2] = $this->generateTestAccount(['foo.bar']);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/users/{$user2->uuid}", [])->assertNotFound();
});
test('subuser cannot strip permissions from a subuser that they do not hold themselves', function () {
    Bus::fake([RevokeSftpAccessJob::class]);
    [$user, $server] = $this->generateTestAccount([Permissions::UserUpdate->value, Permissions::ControlStart->value]);
    /** @var Subuser $subuser */
    $subuser = Subuser::factory()->for(User::factory()->create())->for($server)->create(['permissions' => [
        Permissions::ControlConsole->value,
        Permissions::FileUpdate->value,
        Permissions::ControlStart->value,
        Permissions::WebsocketConnect->value,
    ]]);

    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/users/{$subuser->user->uuid}", ['permissions' => [Permissions::ControlStart->value]])->assertOk();

    expect($subuser->refresh()->permissions)->toEqualCanonicalizing([
        Permissions::ControlConsole->value,
        Permissions::FileUpdate->value,
        Permissions::ControlStart->value,
        Permissions::WebsocketConnect->value,
    ]);
});
test('subuser can change the permissions they hold while preserving the ones they do not', function () {
    Bus::fake([RevokeSftpAccessJob::class]);
    [$user, $server] = $this->generateTestAccount([Permissions::UserUpdate->value, Permissions::ControlStart->value, Permissions::ControlStop->value]);
    /** @var Subuser $subuser */
    $subuser = Subuser::factory()->for(User::factory()->create())->for($server)->create(['permissions' => [
        Permissions::ControlConsole->value,
        Permissions::ControlStart->value,
        Permissions::WebsocketConnect->value,
    ]]);

    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/users/{$subuser->user->uuid}", ['permissions' => [
        Permissions::ControlConsole->value,
        Permissions::ControlStop->value,
        Permissions::WebsocketConnect->value,
    ]])->assertOk();

    expect($subuser->refresh()->permissions)->toEqualCanonicalizing([
        Permissions::ControlConsole->value,
        Permissions::ControlStop->value,
        Permissions::WebsocketConnect->value,
    ]);

    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/users/{$subuser->user->uuid}", ['permissions' => [
        Permissions::ControlStop->value,
        Permissions::FileRead->value,
    ]])->assertForbidden();
    expect($subuser->refresh()->permissions)->toEqualCanonicalizing([
        Permissions::ControlConsole->value,
        Permissions::ControlStop->value,
        Permissions::WebsocketConnect->value,
    ]);
});
