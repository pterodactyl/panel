<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Servers\ServerInstallControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
test('toggle installed server marks installing', function () {
    $server = $this->createServerModel(['status' => null]);
    $response = $this->postJson(route('api.admin.servers.toggle-install', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    expect($server->refresh()->status)->toBe(Server::STATUS_INSTALLING);
});
test('toggle installing server marks installed', function () {
    $server = $this->createServerModel(['status' => Server::STATUS_INSTALLING]);
    $response = $this->postJson(route('api.admin.servers.toggle-install', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    expect($server->refresh()->status)->toBeNull();
});
test('toggle rejected for failed install', function () {
    $server = $this->createServerModel(['status' => Server::STATUS_INSTALL_FAILED]);
    $response = $this->postJson(route('api.admin.servers.toggle-install', ['server' => $server->id]));
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'DisplayException');
});
test('toggle missing server', function () {
    $response = $this->postJson(route('api.admin.servers.toggle-install', ['server' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('non admin forbidden', function () {
    $server = $this->createServerModel();
    $this->actingAsNonAdmin();
    $response = $this->postJson(route('api.admin.servers.toggle-install', ['server' => $server->id]));
    $this->assertAccessDeniedJson($response);
});
