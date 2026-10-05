<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\LogControllerTest;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Contracts\Servers\ReadsServerLogs;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;

uses(ClientApiIntegrationTestCase::class);
test('logs require authentication', function () {
    [, $server] = $this->generateTestAccount();
    $fake = new FakeDaemonServer;
    $this->getJson($this->link($server, '/logs'))->assertUnauthorized();
    $fake->assertNothingHappened();
});
test('subuser without the websocket permission cannot read logs', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ControlConsole->value]);
    $fake = new FakeDaemonServer;
    $this->actingAs($user)->getJson($this->link($server, '/logs'))->assertForbidden();
    $fake->assertNothingHappened();
});
test('user cannot read logs of a server they cannot access', function () {
    [$user] = $this->generateTestAccount();
    [, $server] = $this->generateTestAccount();
    $fake = new FakeDaemonServer;
    $this->actingAs($user)->getJson($this->link($server, '/logs'))->assertNotFound();
    $fake->assertNothingHappened();
});
test('server logs are returned', function (array $permissions) {
    [$user, $server] = $this->generateTestAccount($permissions);
    $fake = new FakeDaemonServer;
    $fake->logs = ['[12:00:00] Starting server', '[12:00:04] Done'];
    $response = $this->actingAs($user)->getJson($this->link($server, '/logs'));
    $response->assertOk();
    $response->assertExactJson(['object' => 'server_logs', 'attributes' => ['lines' => ['[12:00:00] Starting server', '[12:00:04] Done']]]);
    $fake->assertLogsRead(ReadsServerLogs::MAX_LINES);
    expect($fake->callsFor('getLogs')[0]['server_uuid'])->toBe($server->uuid);
})->with([
    'server owner' => [[]],
    'subuser with the websocket permission' => [[Permissions::WebsocketConnect->value]],
]);
test('requested line count is passed to wings', function () {
    [$user, $server] = $this->generateTestAccount();
    $fake = new FakeDaemonServer;
    $this->actingAs($user)->getJson($this->link($server, '/logs?lines=25'))->assertOk()->assertJsonPath('attributes.lines', []);
    $fake->assertLogsRead(25);
});
test('line count is validated', function (string $lines, string $rule) {
    [$user, $server] = $this->generateTestAccount();
    $fake = new FakeDaemonServer;
    $response = $this->actingAs($user)->getJson($this->link($server, '/logs?lines='.$lines));
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'lines');
    $response->assertJsonPath('errors.0.meta.rule', $rule);
    $fake->assertNothingHappened();
})->with([
    'zero' => ['0', 'min'],
    'negative' => ['-5', 'min'],
    'above the maximum' => ['101', 'max'],
    'not a number' => ['all', 'integer'],
]);
test('a server without console output returns no lines', function () {
    [$user, $server] = $this->generateTestAccount();
    $fake = new FakeDaemonServer;
    $fake->logs = null;
    $this->actingAs($user)->getJson($this->link($server, '/logs'))->assertOk()->assertJsonPath('attributes.lines', []);
});
test('error is returned when wings cannot be reached', function () {
    [$user, $server] = $this->generateTestAccount();
    $fake = new FakeDaemonServer;
    $fake->throwable = new DaemonConnectionException(Http::failedRequest([], Response::HTTP_BAD_GATEWAY));
    $response = $this->actingAs($user)->getJson($this->link($server, '/logs'));
    $response->assertStatus(Response::HTTP_BAD_GATEWAY);
    $response->assertJsonPath('errors.0.code', 'DaemonConnectionException');
});
