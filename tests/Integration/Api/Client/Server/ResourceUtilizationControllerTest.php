<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\ResourceUtilizationControllerTest;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;

uses(ClientApiIntegrationTestCase::class);
test('server resource utilization is returned', function () {
    $fake = new FakeDaemonServer;
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $fake->details = [];
    $response = $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/resources");
    $response->assertOk();
    $response->assertJson(['object' => 'stats', 'attributes' => ['current_state' => 'stopped', 'is_suspended' => false, 'resources' => ['memory_bytes' => 0, 'cpu_absolute' => 0, 'disk_bytes' => 0, 'network_rx_bytes' => 0, 'network_tx_bytes' => 0]]]);
    $fake->assertDetailsFetched();
});
test('non-empty daemon payload passes through', function () {
    $fake = new FakeDaemonServer;
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $fake->details = ['state' => 'running', 'utilization' => ['memory_bytes' => 1048576, 'cpu_absolute' => 12.5, 'disk_bytes' => 5242880, 'network' => ['rx_bytes' => 100, 'tx_bytes' => 200], 'uptime' => 3600]];
    $response = $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/resources");
    $response->assertOk();
    $response->assertJsonPath('attributes.current_state', 'running');
    $response->assertJsonPath('attributes.resources.memory_bytes', 1048576);
    $response->assertJsonPath('attributes.resources.cpu_absolute', 12.5);
    $response->assertJsonPath('attributes.resources.network_rx_bytes', 100);
    $response->assertJsonPath('attributes.resources.network_tx_bytes', 200);
    $fake->assertDetailsFetched();
});
test('resource utilization is cached between requests', function () {
    $fake = new FakeDaemonServer;
    [$user, $server] = $this->generateTestAccount();
    $fake->details = ['state' => 'running'];
    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/resources")->assertOk()->assertJsonPath('attributes.current_state', 'running');
    $fake->details = ['state' => 'offline'];
    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/resources")->assertOk()->assertJsonPath('attributes.current_state', 'running');
    expect($fake->callsFor('getDetails'))->toHaveCount(1);
    $this->travel(21)->seconds();
    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/resources")->assertOk()->assertJsonPath('attributes.current_state', 'offline');
});
test('error is returned when wings cannot report resource utilization', function () {
    $fake = new FakeDaemonServer;
    [$user, $server] = $this->generateTestAccount();
    $fake->throwable = new DaemonConnectionException(Http::failedRequest([], Response::HTTP_BAD_GATEWAY));
    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/resources")->assertStatus(Response::HTTP_BAD_GATEWAY)->assertJsonPath('errors.0.code', 'DaemonConnectionException');
});
