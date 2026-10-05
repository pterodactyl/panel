<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Subuser\GetSubusersTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
test('subusers are listed for the server', function () {
    [$user, $server] = $this->generateTestAccount();
    $subusers = User::factory()->times(2)->create()->map(function (User $subuser) use ($server) {
        return Subuser::query()->create(['user_id' => $subuser->id, 'server_id' => $server->id, 'permissions' => [Permissions::ControlConsole->value]]);
    });
    $response = $this->actingAs($user)->getJson($this->link($server, '/users'))->assertOk()->assertJsonPath('object', 'list')->assertJsonCount(2, 'data')->assertJsonPath('data.0.object', 'server_subuser');
    $uuids = collect($response->json('data'))->pluck('attributes.uuid');
    expect($uuids->toArray())->toEqualCanonicalizing($subusers->map(fn ($s) => $s->user->uuid)->toArray());
    expect($response->json('data.0.attributes.permissions'))->toContain(Permissions::ControlConsole->value);
});
test('listing requires user read permission', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ControlConsole->value]);
    $this->getJson($this->link($server, '/users'))->assertUnauthorized();
    $this->actingAs($user)->getJson($this->link($server, '/users'))->assertForbidden();
});
