<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Subuser;

use Mockery;
use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Permission;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
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
    public function testCorrectSubuserIsDeletedFromServer()
    {
        $this->swap(DaemonServerRepository::class, $mock = Mockery::mock(DaemonServerRepository::class));

        [$user, $server] = $this->generateTestAccount();

        /** @var \Pterodactyl\Models\User $differentUser */
        $differentUser = User::factory()->create();

        $real = Uuid::uuid4()->toString();
        // Generate a UUID that lines up with a user in the database if it were to be cast to an int.
        $uuid = $differentUser->id . substr($real, strlen((string) $differentUser->id));

        /** @var \Pterodactyl\Models\User $subuser */
        $subuser = User::factory()->create(['uuid' => $uuid]);

        Subuser::query()->forceCreate([
            'user_id' => $subuser->id,
            'server_id' => $server->id,
            'permissions' => [Permission::ACTION_WEBSOCKET_CONNECT],
        ]);

        $mock->expects('setServer->revokeUserJTI')->with($subuser->id)->andReturnUndefined();

        $this->actingAs($user)->deleteJson($this->link($server) . "/users/$subuser->uuid")->assertNoContent();

        // Try the same test, but this time with a UUID that if cast to an int (shouldn't) line up with
        // anything in the database.
        $uuid = '18180000' . substr(Uuid::uuid4()->toString(), 8);
        /** @var \Pterodactyl\Models\User $subuser */
        $subuser = User::factory()->create(['uuid' => $uuid]);

        Subuser::query()->forceCreate([
            'user_id' => $subuser->id,
            'server_id' => $server->id,
            'permissions' => [Permission::ACTION_WEBSOCKET_CONNECT],
        ]);

        $mock->expects('setServer->revokeUserJTI')->with($subuser->id)->andReturnUndefined();

        $this->actingAs($user)->deleteJson($this->link($server) . "/users/$subuser->uuid")->assertNoContent();
    }
}
