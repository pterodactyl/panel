<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Startup;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class GetStartupAndVariablesTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that the startup command and variables are returned for a server, but only the variables
     * that can be viewed by a user (e.g. user_viewable=true).
     *
     * @dataProvider permissionsDataProvider
     */
    public function testStartupVariablesAreReturnedForServer(array $permissions)
    {
        /** @var \Pterodactyl\Models\Server $server */
        [$user, $server] = $this->generateTestAccount($permissions);

        $egg = $this->cloneEggAndVariables($server->egg);
        // BUNGEE_VERSION should never be returned to the user in this API call, either in
        // the array of variables, or revealed in the startup command.
        $egg->variables()->first()->update([
            'user_viewable' => false,
        ]);

        $server->fill([
            'egg_id' => $egg->id,
            'startup' => 'java {{SERVER_JARFILE}} --version {{BUNGEE_VERSION}}',
        ])->save();
        $server = $server->refresh();

        $response = $this->actingAs($user)->getJson($this->link($server) . '/startup');

        $response->assertOk();
        $response->assertJsonPath('meta.startup_command', 'java bungeecord.jar --version [hidden]');
        $response->assertJsonPath('meta.raw_startup_command', $server->startup);

        $response->assertJsonPath('object', 'list');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.object', EggVariable::RESOURCE_NAME);
        $this->assertJsonTransformedWith($response->json('data.0.attributes'), $egg->variables()->orderBy('id', 'desc')->first());
    }

    /**
     * Test that a user without the required permission, or who does not have any permission to
     * access the server cannot get the startup information for it.
     */
    public function testStartupDataIsNotReturnedWithoutPermission()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_WEBSOCKET_CONNECT]);
        $this->actingAs($user)->getJson($this->link($server) . '/startup')->assertForbidden();

        $user2 = User::factory()->create();
        $this->actingAs($user2)->getJson($this->link($server) . '/startup')->assertNotFound();
    }

    public function permissionsDataProvider(): array
    {
        return [[[]], [[Permission::ACTION_STARTUP_READ]]];
    }
}
