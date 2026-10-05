<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Startup\GetStartupAndVariablesTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
dataset('permissionsDataProvider', fn (): array => [[[]], [[Permissions::StartupRead->value]]]);
test('startup variables are returned for server', function (array $permissions): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount($permissions);
    $egg = $this->cloneEggAndVariables($server->egg);
    // BUNGEE_VERSION should never be returned to the user in this API call, either in
    // the array of variables, or revealed in the startup command.
    $egg->variables()->first()->update(['user_viewable' => false]);
    $server->fill(['egg_id' => $egg->id, 'startup' => 'java {{SERVER_JARFILE}} --version {{BUNGEE_VERSION}}'])->save();
    $server = $server->refresh();
    $response = $this->actingAs($user)->getJson($this->link($server).'/startup');
    $response->assertOk();
    $response->assertJsonPath('meta.startup_command', 'java bungeecord.jar --version [hidden]');
    $response->assertJsonPath('meta.raw_startup_command', $server->startup);
    $response->assertJsonPath('object', 'list');
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.object', EggVariable::RESOURCE_NAME);

    $variable = $egg->variables[1];
    expect($response->json('data.0.attributes'))->toBe([
        'name' => $variable->name,
        'description' => $variable->description,
        'env_variable' => 'SERVER_JARFILE',
        'default_value' => 'bungeecord.jar',
        'server_value' => null,
        'is_editable' => true,
        'rules' => $variable->rules,
    ]);
})->with('permissionsDataProvider');
test('startup data is not returned without permission', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $this->actingAs($user)->getJson($this->link($server).'/startup')->assertForbidden();
    $user2 = User::factory()->create();
    $this->actingAs($user2)->getJson($this->link($server).'/startup')->assertNotFound();
});
