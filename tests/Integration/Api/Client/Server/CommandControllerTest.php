<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\CommandControllerTest;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonCommand;

uses(ClientApiIntegrationTestCase::class);
test('validation error is returned if no command is present', function () {
    [$user, $server] = $this->generateTestAccount();
    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/command", ['command' => '']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.rule', 'required');
});
test('subuser without permission receives error', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/command", ['command' => 'say Test']);
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});
test('command can send to server', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ControlConsole->value]);
    $fake = new FakeDaemonCommand;
    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/command", ['command' => 'say Test']);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $fake->assertSent('say Test');
    Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), "/api/servers/{$server->uuid}/"));
});
test('error is returned when server is offline', function () {
    [$user, $server] = $this->generateTestAccount();
    $fake = new FakeDaemonCommand;
    $fake->throwable = new DaemonConnectionException(Http::failedRequest([], Response::HTTP_BAD_GATEWAY));
    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/command", ['command' => 'say Test']);
    $response->assertStatus(Response::HTTP_BAD_GATEWAY);
    $response->assertJsonPath('errors.0.code', 'HttpException');
    $response->assertJsonPath('errors.0.detail', 'Server must be online in order to send commands.');
    $fake->assertSent('say Test');
});
