<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Subuser;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Permission;
use Illuminate\Support\Facades\Bus;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use PHPUnit\Framework\Attributes\TestWith;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class DeleteSubuserTest extends ClientApiIntegrationTestCase
{
    /**
     * Guards against PHP's exciting behavior where a string can be cast to an int and only
     * the first numeric digits are returned. This causes UUIDs to be returned as an int when
     * looking up users, thus returning the wrong subusers (or no subuser at all).
     *
     * For example, 12aaaaaa-bbbb-cccc-ddddeeeeffff would be cast to "12" if you tried to cast
     * it to an integer. Then, in the deep API middlewares you would end up trying to load a user
     * with an ID of 12, which may or may not exist and be wrongly assigned to the model object.
     *
     * @see https://github.com/pterodactyl/panel/issues/2359
     */
    #[TestWith([null])]
    #[TestWith(['18180000'])]
    public function testCorrectSubuserIsDeletedFromServer(?string $prefix)
    {
        Bus::fake([RevokeSftpAccessJob::class]);

        [$user, $server] = $this->generateTestAccount();

        /** @var User $differentUser */
        $differentUser = User::factory()->create();

        $real = Uuid::uuid4()->toString();
        // Generate a UUID that lines up with a user in the database if it were to be cast to an int.
        $uuid = ($prefix ?: $differentUser->id) . substr($real, strlen($prefix ?: (string) $differentUser->id));

        /** @var User $subuser */
        $subuser = User::factory()->create(['uuid' => $uuid]);

        Subuser::query()->forceCreate([
            'user_id' => $subuser->id,
            'server_id' => $server->id,
            'permissions' => [Permission::ACTION_WEBSOCKET_CONNECT],
        ]);

        $this->withoutExceptionHandling()
            ->actingAs($user)
            ->deleteJson($this->link($server) . "/users/$subuser->uuid")->assertNoContent();

        Bus::assertDispatchedTimes(function (RevokeSftpAccessJob $job) use ($subuser, $server) {
            return $job->user === $subuser->uuid && $job->target->is($server);
        });
    }
}
