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
