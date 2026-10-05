<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\PowerControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonPower;

uses(ClientApiIntegrationTestCase::class);
/**
 * Returns invalid permission combinations for a given power action.
 */
dataset('invalidPermissionDataProvider', function () {
    return [['start', [Permissions::ControlStop->value, Permissions::ControlRestart->value]], ['stop', [Permissions::ControlStart->value]], ['kill', [Permissions::ControlStart->value, Permissions::ControlRestart->value]], ['restart', [Permissions::ControlStop->value, Permissions::ControlStart->value]], ['random', [Permissions::ControlStart->value]]];
});
dataset('validPowerActionDataProvider', function () {
    return [
        ['start', Permissions::ControlStart->value],
        ['stop', Permissions::ControlStop->value],
        ['restart', Permissions::ControlRestart->value],
        ['kill', Permissions::ControlStop->value],
        // Yes, these spaces are intentional. You should be able to send values with or without
        // a space on the start/end since we should be trimming the values.
        [' restart', Permissions::ControlRestart->value],
        ['kill ', Permissions::ControlStop->value],
    ];
});
test('subuser without permissions receives error', function (string $action, array $permissions) {
    [$user, $server] = $this->generateTestAccount($permissions);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/power", ['signal' => $action])->assertStatus(Response::HTTP_FORBIDDEN);
})->with('invalidPermissionDataProvider');
test('invalid power signal results in error', function () {
    [$user, $server] = $this->generateTestAccount();
    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/power", ['signal' => 'invalid']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.rule', 'in');
    $response->assertJsonPath('errors.0.detail', 'The selected signal is invalid.');
});
test('action can be sent to server', function (string $action, string $permission) {
    $fake = new FakeDaemonPower;
    [$user, $server] = $this->generateTestAccount([$permission]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/power", ['signal' => $action])->assertStatus(Response::HTTP_NO_CONTENT);
    $fake->assertSentTo(mb_trim($action), $server->uuid);
})->with('validPowerActionDataProvider');
