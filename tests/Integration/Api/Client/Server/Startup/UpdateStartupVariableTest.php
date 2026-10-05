<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Startup\UpdateStartupVariableTest;

use Illuminate\Http\Response;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
dataset('permissionsDataProvider', fn (): array => [[[]], [[Permissions::StartupUpdate->value]]]);
test('startup variable can be updated', function (array $permissions): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount($permissions);
    $server->fill(['startup' => 'java {{SERVER_JARFILE}} --version {{BUNGEE_VERSION}}'])->save();
    $response = $this->actingAs($user)->putJson($this->link($server).'/startup/variable', ['key' => 'BUNGEE_VERSION', 'value' => '1.2.3']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.code', 'ValidationException');
    $response->assertJsonPath('errors.0.detail', 'The value may only contain letters and numbers.');
    $response = $this->actingAs($user)->putJson($this->link($server).'/startup/variable', ['key' => 'BUNGEE_VERSION', 'value' => '123']);
    $response->assertOk();
    $response->assertJsonPath('object', EggVariable::RESOURCE_NAME);

    $variable = $server->variables[0];
    expect($response->json('attributes'))->toBe([
        'name' => $variable->name,
        'description' => $variable->description,
        'env_variable' => 'BUNGEE_VERSION',
        'default_value' => $variable->default_value,
        'server_value' => '123',
        'is_editable' => true,
        'rules' => $variable->rules,
    ]);
    $this->assertDatabaseHas('server_variables', ['server_id' => $server->id, 'variable_id' => $variable->id, 'variable_value' => '123']);
    $response->assertJsonPath('meta.startup_command', 'java bungeecord.jar --version 123');
    $response->assertJsonPath('meta.raw_startup_command', $server->startup);
})->with('permissionsDataProvider');
test('startup variable cannot be updated if not user viewable or editable', function (array $permissions): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount($permissions);
    $egg = $this->cloneEggAndVariables($server->egg);
    $egg->variables()->where('env_variable', 'BUNGEE_VERSION')->update(['user_viewable' => false]);
    $egg->variables()->where('env_variable', 'SERVER_JARFILE')->update(['user_editable' => false]);
    $server->fill(['egg_id' => $egg->id])->save();
    $server->refresh();
    $response = $this->actingAs($user)->putJson($this->link($server).'/startup/variable', ['key' => 'BUNGEE_VERSION', 'value' => '123']);
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'BadRequestHttpException');
    $response->assertJsonPath('errors.0.detail', 'The environment variable you are trying to edit does not exist.');
    $response = $this->actingAs($user)->putJson($this->link($server).'/startup/variable', ['key' => 'SERVER_JARFILE', 'value' => 'server2.jar']);
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'BadRequestHttpException');
    $response->assertJsonPath('errors.0.detail', 'The environment variable you are trying to edit is read-only.');
})->with('permissionsDataProvider');
test('hidden variables are not returned in startup command when updating variable', function (): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount();
    $egg = $this->cloneEggAndVariables($server->egg);
    $egg->variables()->first()->update(['user_viewable' => false]);
    $server->fill(['egg_id' => $egg->id, 'startup' => 'java {{SERVER_JARFILE}} --version {{BUNGEE_VERSION}}'])->save();
    $server->refresh();
    $response = $this->actingAs($user)->putJson($this->link($server).'/startup/variable', ['key' => 'SERVER_JARFILE', 'value' => 'server2.jar']);
    $response->assertOk();
    $response->assertJsonPath('meta.startup_command', 'java server2.jar --version [hidden]');
    $response->assertJsonPath('meta.raw_startup_command', $server->startup);
});
test('egg variable with nullable string is not required', function (): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount();
    $egg = $this->cloneEggAndVariables($server->egg);
    $egg->variables()->first()->update(['rules' => 'nullable|string']);
    $server->fill(['egg_id' => $egg->id])->save();
    $server->refresh();
    $response = $this->actingAs($user)->putJson($this->link($server).'/startup/variable', ['key' => 'BUNGEE_VERSION', 'value' => '']);
    $response->assertOk();
    $response->assertJsonPath('attributes.server_value', null);
});
test('startup variable cannot be updated if not user viewable', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $this->actingAs($user)->putJson($this->link($server).'/startup/variable')->assertForbidden();
    $user2 = User::factory()->create();
    $this->actingAs($user2)->putJson($this->link($server).'/startup/variable')->assertNotFound();
});
