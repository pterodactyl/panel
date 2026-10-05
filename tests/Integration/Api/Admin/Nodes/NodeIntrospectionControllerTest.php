<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Nodes\NodeIntrospectionControllerTest;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonConfiguration;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root administrator. */
dataset('introspectionEndpointsDataProvider', function () {
    return [['getJson', 'api.admin.nodes.system-information'], ['postJson', 'api.admin.nodes.deploy-token'], ['getJson', 'api.admin.nodes.utilization']];
});
test('get system information', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $information = ['version' => '1.11.0', 'os' => 'linux', 'architecture' => 'amd64', 'kernel_version' => '5.15.0-91-generic', 'cpu_count' => 8];
    $fake = new FakeDaemonConfiguration;
    $fake->systemInformation = $information;
    $response = $this->getJson(route('api.admin.nodes.system-information', ['node' => $node->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['version', 'os', 'architecture', 'kernel_version', 'cpu_count']);
    $response->assertJson($information);
    $fake->assertSystemInformationFetched();
    Http::assertSent(fn (HttpRequest $request): bool => $request->hasHeader('Authorization', 'Bearer '.$node->getDecryptedKey()));
});
test('get system information for missing node', function () {
    $response = $this->getJson(route('api.admin.nodes.system-information', ['node' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('deploy token creates key', function () {
    $node = Node::factory()->for(Location::factory())->create();
    config()->set('app.url', 'https://panel.example.test');
    config()->set('app.debug', true);
    $this->assertDatabaseMissing('api_keys', ['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION, 'r_nodes' => 3]);
    $response = $this->postJson(route('api.admin.nodes.deploy-token', ['node' => $node->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['node', 'token', 'panel_url', 'allow_insecure']);
    $response->assertJsonPath('node', $node->id);
    $response->assertJsonPath('panel_url', 'https://panel.example.test');
    $response->assertJsonPath('allow_insecure', true);
    $key = ApiKey::query()->where('user_id', $this->getAdminUser()->id)->where('key_type', ApiKey::TYPE_APPLICATION)->where('r_nodes', 3)->firstOrFail();
    foreach (AdminAcl::getResourceList() as $resource) {
        expect($key->getAttribute(AdminAcl::COLUMN_IDENTIFIER.$resource))->toBe($resource === AdminAcl::RESOURCE_NODES ? AdminAcl::READ | AdminAcl::WRITE : AdminAcl::NONE);
    }
    // The returned token is the identifier concatenated with the decrypted secret.
    expect($response->json('token'))->toStartWith($key->identifier);
});
test('deploy token reuses existing key', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $first = $this->postJson(route('api.admin.nodes.deploy-token', ['node' => $node->id]));
    $first->assertStatus(Response::HTTP_OK);
    $second = $this->postJson(route('api.admin.nodes.deploy-token', ['node' => $node->id]));
    $second->assertStatus(Response::HTTP_OK);
    expect($second->json('token'))->toBe($first->json('token'));
    $count = ApiKey::query()->where('user_id', $this->getAdminUser()->id)->where('key_type', ApiKey::TYPE_APPLICATION)->where('r_nodes', 3)->count();
    expect($count)->toBe(1);
});
test('deploy token never reuses a key with wider permissions', function (): void {
    $node = Node::factory()->for(Location::factory())->create();
    $broad = ApiKey::factory()->create(['user_id' => $this->getAdminUser()->id, 'key_type' => ApiKey::TYPE_APPLICATION, 'r_nodes' => 3, 'r_servers' => 3, 'r_users' => 3]);
    $response = $this->postJson(route('api.admin.nodes.deploy-token', ['node' => $node->id]))->assertOk();
    expect($response->json('token'))->not->toStartWith($broad->identifier);
});
test('deploy token for missing node', function () {
    $response = $this->postJson(route('api.admin.nodes.deploy-token', ['node' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('get utilization', function () {
    $server = $this->createServerModel();
    $node = Node::query()->findOrFail($server->node_id);
    $response = $this->getJson(route('api.admin.nodes.utilization', ['node' => $node->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['memory' => ['value', 'max', 'percent', 'css'], 'disk' => ['value', 'max', 'percent', 'css']]);
    $response->assertJsonPath('memory.max', number_format($node->memory));
    $response->assertJsonPath('disk.max', number_format($node->disk));
    $response->assertJsonPath('memory.value', number_format($server->memory));
    $response->assertJsonPath('disk.value', number_format($server->disk));
});
test('get utilization for missing node', function () {
    $response = $this->getJson(route('api.admin.nodes.utilization', ['node' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('non admin forbidden', function (string $method, string $routeName) {
    $this->actingAsNonAdmin();
    $node = Node::factory()->for(Location::factory())->create();
    $response = $this->{$method}(route($routeName, ['node' => $node->id]));
    $this->assertAccessDeniedJson($response);
})->with('introspectionEndpointsDataProvider');
