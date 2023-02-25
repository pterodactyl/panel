<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Startup;

use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class UpdateStartupVariableTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that a startup variable can be edited successfully for a server.
     *
     * @dataProvider permissionsDataProvider
     */
    public function testStartupVariableCanBeUpdated(array $permissions)
    {
        /** @var \Pterodactyl\Models\Server $server */
        [$user, $server] = $this->generateTestAccount($permissions);
        $server->fill([
            'startup' => 'java {{SERVER_JARFILE}} --version {{BUNGEE_VERSION}}',
        ])->save();

        $response = $this->actingAs($user)->putJson($this->link($server) . '/startup/variable', [
            'key' => 'BUNGEE_VERSION',
            'value' => '1.2.3',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.0.code', 'ValidationException');
        $response->assertJsonPath('errors.0.detail', 'The value may only contain letters and numbers.');

        $response = $this->actingAs($user)->putJson($this->link($server) . '/startup/variable', [
            'key' => 'BUNGEE_VERSION',
            'value' => '123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('object', EggVariable::RESOURCE_NAME);
        $this->assertJsonTransformedWith($response->json('attributes'), $server->variables[0]);
        $response->assertJsonPath('meta.startup_command', 'java bungeecord.jar --version 123');
        $response->assertJsonPath('meta.raw_startup_command', $server->startup);
    }

    /**
     * Test that variables that are either not user_viewable, or not user_editable, cannot be
     * updated via this endpoint.
     *
     * @dataProvider permissionsDataProvider
     */
    public function testStartupVariableCannotBeUpdatedIfNotUserViewableOrEditable(array $permissions)
    {
        /** @var \Pterodactyl\Models\Server $server */
        [$user, $server] = $this->generateTestAccount($permissions);

        $egg = $this->cloneEggAndVariables($server->egg);
        $egg->variables()->where('env_variable', 'BUNGEE_VERSION')->update(['user_viewable' => false]);
        $egg->variables()->where('env_variable', 'SERVER_JARFILE')->update(['user_editable' => false]);

        $server->fill(['egg_id' => $egg->id])->save();
        $server->refresh();

        $response = $this->actingAs($user)->putJson($this->link($server) . '/startup/variable', [
            'key' => 'BUNGEE_VERSION',
            'value' => '123',
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonPath('errors.0.code', 'BadRequestHttpException');
        $response->assertJsonPath('errors.0.detail', 'The environment variable you are trying to edit does not exist.');

        $response = $this->actingAs($user)->putJson($this->link($server) . '/startup/variable', [
            'key' => 'SERVER_JARFILE',
            'value' => 'server2.jar',
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonPath('errors.0.code', 'BadRequestHttpException');
        $response->assertJsonPath('errors.0.detail', 'The environment variable you are trying to edit is read-only.');
    }

    /**
     * Test that a hidden variable is not included in the startup_command output for the server if
     * a different variable is updated.
     */
    public function testHiddenVariablesAreNotReturnedInStartupCommandWhenUpdatingVariable()
    {
        /** @var \Pterodactyl\Models\Server $server */
        [$user, $server] = $this->generateTestAccount();

        $egg = $this->cloneEggAndVariables($server->egg);
        $egg->variables()->first()->update(['user_viewable' => false]);

        $server->fill([
            'egg_id' => $egg->id,
            'startup' => 'java {{SERVER_JARFILE}} --version {{BUNGEE_VERSION}}',
        ])->save();

        $server->refresh();

        $response = $this->actingAs($user)->putJson($this->link($server) . '/startup/variable', [
            'key' => 'SERVER_JARFILE',
            'value' => 'server2.jar',
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.startup_command', 'java server2.jar --version [hidden]');
        $response->assertJsonPath('meta.raw_startup_command', $server->startup);
    }

    /**
     * Test that an egg variable with a validation rule of 'nullable|string' works if no value
     * is passed through in the request.
     *
     * @see https://github.com/pterodactyl/panel/issues/2433
     */
    public function testEggVariableWithNullableStringIsNotRequired()
    {
        /** @var \Pterodactyl\Models\Server $server */
        [$user, $server] = $this->generateTestAccount();

        $egg = $this->cloneEggAndVariables($server->egg);
        $egg->variables()->first()->update(['rules' => 'nullable|string']);

        $server->fill(['egg_id' => $egg->id])->save();
        $server->refresh();

        $response = $this->actingAs($user)->putJson($this->link($server) . '/startup/variable', [
            'key' => 'BUNGEE_VERSION',
            'value' => '',
        ]);

        $response->assertOk();
        $response->assertJsonPath('attributes.server_value', null);
    }

    /**
     * Test that a variable cannot be updated if the user does not have permission to perform
     * that action, or they aren't assigned at all to the server.
     */
    public function testStartupVariableCannotBeUpdatedIfNotUserViewable()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_WEBSOCKET_CONNECT]);
        $this->actingAs($user)->putJson($this->link($server) . '/startup/variable')->assertForbidden();

        $user2 = User::factory()->create();
        $this->actingAs($user2)->putJson($this->link($server) . '/startup/variable')->assertNotFound();
    }

    public static function permissionsDataProvider(): array
    {
        return [[[]], [[Permission::ACTION_STARTUP_UPDATE]]];
    }
}
