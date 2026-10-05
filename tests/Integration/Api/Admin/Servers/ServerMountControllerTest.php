<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Servers\ServerMountControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Mount endpoints that must reject non-administrators. */
dataset('mountEndpointsDataProvider', function () {
    return [['getJson', 'api.admin.servers.mounts'], ['postJson', 'api.admin.servers.mounts.store'], ['deleteJson', 'api.admin.servers.mounts.delete']];
});
test('list server mounts', function () {
    $server = $this->createServerModel();
    $mount = createEligibleMount($server);
    $response = $this->getJson(route('api.admin.servers.mounts', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('object', 'list');
    $response->assertJsonPath('data.0.object', 'mount');
    $response->assertJsonPath('data.0.attributes.id', $mount->id);
    $response->assertJsonPath('data.0.attributes.mounted', false);
});
test('list reflects mounted state', function () {
    $server = $this->createServerModel();
    $mount = createEligibleMount($server);
    $this->postJson(route('api.admin.servers.mounts.store', ['server' => $server->id]), ['mount_id' => $mount->id])->assertStatus(Response::HTTP_NO_CONTENT);
    $response = $this->getJson(route('api.admin.servers.mounts', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('data.0.attributes.id', $mount->id);
    $response->assertJsonPath('data.0.attributes.mounted', true);
});
test('mount can be attached and detached', function () {
    $server = $this->createServerModel();
    $mount = createEligibleMount($server);
    $store = $this->postJson(route('api.admin.servers.mounts.store', ['server' => $server->id]), ['mount_id' => $mount->id]);
    $store->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('mount_server', ['mount_id' => $mount->id, 'server_id' => $server->id]);
    $delete = $this->deleteJson(route('api.admin.servers.mounts.delete', ['server' => $server->id, 'mount' => $mount->id]));
    $delete->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('mount_server', ['mount_id' => $mount->id, 'server_id' => $server->id]);
});
test('ineligible mount cannot be attached', function () {
    $server = $this->createServerModel();
    /** @var Mount $mount */
    $mount = Mount::factory()->create();
    $response = $this->postJson(route('api.admin.servers.mounts.store', ['server' => $server->id]), ['mount_id' => $mount->id]);
    $this->assertNotFoundJson($response);
    $this->assertDatabaseMissing('mount_server', ['mount_id' => $mount->id, 'server_id' => $server->id]);
});
test('store validation', function () {
    $server = $this->createServerModel();
    $response = $this->postJson(route('api.admin.servers.mounts.store', ['server' => $server->id]), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'mount_id');
});
test('list missing server', function () {
    $response = $this->getJson(route('api.admin.servers.mounts', ['server' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('non admin forbidden', function (string $method, string $routeName) {
    $server = $this->createServerModel();
    $mount = createEligibleMount($server);
    $this->actingAsNonAdmin();
    $response = $this->{$method}(route($routeName, ['server' => $server->id, 'mount' => $mount->id]));
    $this->assertAccessDeniedJson($response);
})->with('mountEndpointsDataProvider');
/** Create a mount eligible to attach to the server (assigned to its egg and node). */
function createEligibleMount(Server $server): Mount
{
    /** @var Mount $mount */
    $mount = Mount::factory()->create();
    $mount->eggs()->attach($server->egg_id);
    $mount->nodes()->attach($server->node_id);

    return $mount;
}
