<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\GetServerTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
test('server details are returned to the owner', function () {
    [$user, $server] = $this->generateTestAccount();
    $this->actingAs($user)->getJson($this->link($server))->assertOk()->assertJsonPath('object', 'server')->assertJsonPath('attributes.uuid', $server->uuid)->assertJsonPath('attributes.name', $server->name)->assertJsonPath('meta.is_server_owner', true)->assertJsonPath('meta.user_permissions.0', '*');
});
test('server details expose the tags of its egg', function () {
    [$user, $server] = $this->generateTestAccount();
    $this->actingAs($user)->getJson($this->link($server))->assertOk()->assertJsonPath('attributes.egg_tags', []);

    $server->egg->tags()->attach(Tag::factory()->create(['name' => 'Minecraft', 'slug' => 'minecraft']));
    $this->actingAs($user)->getJson($this->link($server))->assertOk()->assertJsonPath('attributes.egg_tags', ['minecraft']);
});
test('subuser permissions are returned in meta', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ControlConsole->value, Permissions::WebsocketConnect->value]);
    $response = $this->actingAs($user)->getJson($this->link($server))->assertOk()->assertJsonPath('meta.is_server_owner', false);
    expect($response->json('meta.user_permissions'))->toEqualCanonicalizing([Permissions::ControlConsole->value, Permissions::WebsocketConnect->value]);
});
test('unrelated user cannot view the server', function () {
    [, $server] = $this->generateTestAccount();
    [$other] = $this->generateTestAccount();
    $this->getJson($this->link($server))->assertUnauthorized();
    $this->actingAs($other)->getJson($this->link($server))->assertNotFound();
});
