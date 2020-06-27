<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

class ClientControllerTest extends IntegrationTestCase
{
    /**
     * Cleanup after tests are run.
     */
    protected function tearDown(): void
    {
        Server::query()->forceDelete();
        User::query()->forceDelete();
        Node::query()->forceDelete();
        Location::query()->forceDelete();

        parent::tearDown();
    }

    /**
     * Test that only the servers a logged in user is assigned to are returned by the
     * API endpoint. Obviously there are cases such as being an administrator or being
     * a subuser, but for this test we just want to test a basic scenario and pretend
     * subusers do not exist at all.
     */
    public function testOnlyLoggedInUsersServersAreReturned()
    {
        /** @var \Pterodactyl\Models\User[] $users */
        $users = factory(User::class)->times(3)->create();

        /** @var \Pterodactyl\Models\Server[] $servers */
        $servers = [
            $this->createServerModel(['user_id' => $users[0]->id]),
            $this->createServerModel(['user_id' => $users[1]->id]),
            $this->createServerModel(['user_id' => $users[2]->id]),
        ];

        $response = $this->actingAs($users[0])->getJson('/api/client');

        $response->assertOk();
        $response->assertJsonPath('object', 'list');
        $response->assertJsonPath('data.0.object', Server::RESOURCE_NAME);
        $response->assertJsonPath('data.0.attributes.identifier', $servers[0]->uuidShort);
        $response->assertJsonPath('data.0.attributes.server_owner', true);
        $response->assertJsonPath('meta.pagination.total', 1);
        $response->assertJsonPath('meta.pagination.per_page', config('pterodactyl.paginate.frontend.servers'));
    }

    /**
     * Tests that all of the servers on the system are returned when making the request as an
     * administrator and including the ?filter=all parameter in the URL.
     */
    public function testFilterIncludeAllServersWhenAdministrator()
    {
        /** @var \Pterodactyl\Models\User[] $users */
        $users = factory(User::class)->times(3)->create();
        $users[0]->root_admin = true;

        $servers = [
            $this->createServerModel(['user_id' => $users[0]->id]),
            $this->createServerModel(['user_id' => $users[1]->id]),
            $this->createServerModel(['user_id' => $users[2]->id]),
        ];

        $response = $this->actingAs($users[0])->getJson('/api/client?filter=all');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');

        for ($i = 0; $i < 3; $i++) {
            $response->assertJsonPath("data.{$i}.attributes.server_owner", $i === 0);
            $response->assertJsonPath("data.{$i}.attributes.identifier", $servers[$i]->uuidShort);
        }
    }

    /**
     * Test that servers where the user is a subuser are returned by default in the API call.
     */
    public function testServersUserIsASubuserOfAreReturned()
    {
        /** @var \Pterodactyl\Models\User[] $users */
        $users = factory(User::class)->times(3)->create();
        $servers = [
            $this->createServerModel(['user_id' => $users[0]->id]),
            $this->createServerModel(['user_id' => $users[1]->id]),
            $this->createServerModel(['user_id' => $users[2]->id]),
        ];

        // Set user 0 as a subuser of server 1. Thus, we should get two servers
        // back in the response when making the API call as user 0.
        Subuser::query()->create([
            'user_id' => $users[0]->id,
            'server_id' => $servers[1]->id,
            'permissions' => [Permission::ACTION_WEBSOCKET_CONNECT],
        ]);

        $response = $this->actingAs($users[0])->getJson('/api/client');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.server_owner', true);
        $response->assertJsonPath('data.0.attributes.identifier', $servers[0]->uuidShort);
        $response->assertJsonPath('data.1.attributes.server_owner', false);
        $response->assertJsonPath('data.1.attributes.identifier', $servers[1]->uuidShort);
    }

    /**
     * Returns only servers that the user owns, not servers they are a subuser of.
     */
    public function testFilterOnlyOwnerServers()
    {
        /** @var \Pterodactyl\Models\User[] $users */
        $users = factory(User::class)->times(3)->create();
        $servers = [
            $this->createServerModel(['user_id' => $users[0]->id]),
            $this->createServerModel(['user_id' => $users[1]->id]),
            $this->createServerModel(['user_id' => $users[2]->id]),
        ];

        // Set user 0 as a subuser of server 1. Thus, we should get two servers
        // back in the response when making the API call as user 0.
        Subuser::query()->create([
            'user_id' => $users[0]->id,
            'server_id' => $servers[1]->id,
            'permissions' => [Permission::ACTION_WEBSOCKET_CONNECT],
        ]);

        $response = $this->actingAs($users[0])->getJson('/api/client?filter=owner');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.server_owner', true);
        $response->assertJsonPath('data.0.attributes.identifier', $servers[0]->uuidShort);
    }

    /**
     * Tests that the permissions from the Panel are returned correctly.
     */
    public function testPermissionsAreReturned()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = factory(User::class)->create();

        $this->actingAs($user)
            ->getJson('/api/client/permissions')
            ->assertOk()
            ->assertJson([
                'object' => 'system_permissions',
                'attributes' => [
                    'permissions' => Permission::permissions()->toArray(),
                ],
            ]);
    }
}
