<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Subuser;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class UpdateSubuserTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that the correct permissions are applied to the account when making updates
     * to a subusers permissions.
     */
    public function testCorrectPermissionsAreRequiredForUpdating()
    {
        [$user, $server] = $this->generateTestAccount(['user.read']);

        $subuser = Subuser::factory()
            ->for(User::factory()->create())
            ->for($server)
            ->create([
                'permissions' => ['control.start'],
            ]);

        $this->postJson(
            $endpoint = "/api/client/servers/$server->uuid/users/{$subuser->user->uuid}",
            $data = [
                'permissions' => [
                    'control.start',
                    'control.stop',
                ],
            ]
        )
            ->assertUnauthorized();

        $this->actingAs($subuser->user)->postJson($endpoint, $data)->assertForbidden();
        $this->actingAs($user)->postJson($endpoint, $data)->assertForbidden();

        $server->subusers()->where('user_id', $user->id)->update([
            'permissions' => [
                Permission::ACTION_USER_UPDATE,
                Permission::ACTION_CONTROL_START,
                Permission::ACTION_CONTROL_STOP,
            ],
        ]);

        $this->postJson($endpoint, $data)->assertOk();
    }

    /**
     * Tests that permissions for the account are updated and any extraneous values
     * we don't know about are removed.
     */
    public function testPermissionsAreSavedToAccount()
    {
        [$user, $server] = $this->generateTestAccount();

        /** @var Subuser $subuser */
        $subuser = Subuser::factory()
            ->for(User::factory()->create())
            ->for($server)
            ->create([
                'permissions' => ['control.restart', 'websocket.connect', 'foo.bar'],
            ]);

        $this->actingAs($user)
            ->postJson("/api/client/servers/$server->uuid/users/{$subuser->user->uuid}", [
                'permissions' => [
                    'control.start',
                    'control.stop',
                    'control.stop',
                    'foo.bar',
                    'power.fake',
                ],
            ])
            ->assertOk();

        $subuser->refresh();
        $this->assertEqualsCanonicalizing(
            ['control.start', 'control.stop', 'websocket.connect'],
            $subuser->permissions
        );
    }

    /**
     * Ensure a subuser cannot assign permissions to an account that they do not have
     * themselves.
     */
    public function testUserCannotAssignPermissionsTheyDoNotHave()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_USER_READ, Permission::ACTION_USER_UPDATE]);

        $subuser = Subuser::factory()
            ->for(User::factory()->create())
            ->for($server)
            ->create(['permissions' => ['foo.bar']]);

        $this->actingAs($user)
            ->postJson("/api/client/servers/$server->uuid/users/{$subuser->user->uuid}", [
                'permissions' => [Permission::ACTION_USER_READ, Permission::ACTION_CONTROL_CONSOLE],
            ])
            ->assertForbidden();

        $this->assertEqualsCanonicalizing(['foo.bar'], $subuser->refresh()->permissions);
    }

    /**
     * Test that a user cannot update thyself.
     */
    public function testUserCannotUpdateSelf()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_USER_READ, Permission::ACTION_USER_UPDATE]);

        $this->actingAs($user)
            ->postJson("/api/client/servers/$server->uuid/users/$user->uuid", [])
            ->assertForbidden();
    }

    /**
     * Test that an error is returned if you attempt to update a subuser on a different account.
     */
    public function testCannotUpdateSubuserForDifferentServer()
    {
        [$user, $server] = $this->generateTestAccount();
        [$user2] = $this->generateTestAccount(['foo.bar']);

        $this->actingAs($user)
            ->postJson("/api/client/servers/$server->uuid/users/$user2->uuid", [])
            ->assertNotFound();
    }
}
