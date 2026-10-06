<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Subuser\DeleteSubuserTest;

use Illuminate\Support\Facades\Bus;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Ramsey\Uuid\Uuid;

uses(ClientApiIntegrationTestCase::class);
test('correct subuser is deleted from server', function (?string $prefix) {
    Bus::fake([RevokeSftpAccessJob::class]);
    [$user, $server] = $this->generateTestAccount();
    /** @var User $differentUser */
    $differentUser = User::factory()->create();
    $real = Uuid::uuid4()->toString();
    // Generate a UUID that lines up with a user in the database if it were to be cast to an int.
    $uuid = ($prefix ?: $differentUser->id).mb_substr($real, mb_strlen($prefix ?: (string) $differentUser->id));
    /** @var User $subuser */
    $subuser = User::factory()->create(['uuid' => $uuid]);
    Subuser::query()->forceCreate(['user_id' => $subuser->id, 'server_id' => $server->id, 'permissions' => [Permissions::WebsocketConnect->value]]);
    $this->withoutExceptionHandling()->actingAs($user)->deleteJson($this->link($server)."/users/{$subuser->uuid}")->assertNoContent();
    Bus::assertDispatchedTimes(function (RevokeSftpAccessJob $job) use ($subuser, $server) {
        return $job->user === $subuser->uuid && $job->target->is($server);
    });
})->with([
    'without a numeric prefix' => [null],
    'with a numeric prefix' => ['18180000'],
]);
test('subuser cannot delete a subuser holding permissions they do not have', function () {
    Bus::fake([RevokeSftpAccessJob::class]);
    [$user, $server] = $this->generateTestAccount([Permissions::UserDelete->value, Permissions::ControlStart->value]);
    /** @var Subuser $target */
    $target = Subuser::factory()->for(User::factory()->create())->for($server)->create(['permissions' => [
        Permissions::ControlStart->value,
        Permissions::ControlConsole->value,
        Permissions::WebsocketConnect->value,
    ]]);

    $this->actingAs($user)->deleteJson($this->link($server)."/users/{$target->user->uuid}")->assertForbidden();
    expect(Subuser::query()->whereKey($target->id)->exists())->toBeTrue();
    Bus::assertNotDispatched(RevokeSftpAccessJob::class);
});
test('subuser can delete a subuser whose permissions are a subset of their own', function () {
    Bus::fake([RevokeSftpAccessJob::class]);
    [$user, $server] = $this->generateTestAccount([Permissions::UserDelete->value, Permissions::ControlStart->value, Permissions::ControlConsole->value]);
    /** @var Subuser $target */
    $target = Subuser::factory()->for(User::factory()->create())->for($server)->create(['permissions' => [
        Permissions::ControlStart->value,
        Permissions::WebsocketConnect->value,
    ]]);

    $this->actingAs($user)->deleteJson($this->link($server)."/users/{$target->user->uuid}")->assertNoContent();
    expect(Subuser::query()->whereKey($target->id)->exists())->toBeFalse();
});
