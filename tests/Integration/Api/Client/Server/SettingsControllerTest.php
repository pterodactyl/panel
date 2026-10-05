<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\SettingsControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonServer;

uses(ClientApiIntegrationTestCase::class);
dataset('renamePermissionsDataProvider', fn (): array => [[[]], [[Permissions::SettingsRename->value]]]);
dataset('reinstallPermissionsDataProvider', fn (): array => [[[]], [[Permissions::SettingsReinstall->value]]]);
test('server name can be changed', function (array $permissions): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount($permissions);
    $originalName = $server->name;
    $originalDescription = $server->description;
    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/rename", ['name' => '', 'description' => '']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.rule', 'required');

    $server = $server->refresh();
    expect($server->name)->toBe($originalName);
    expect($server->description)->toBe($originalDescription);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/rename", ['name' => 'Test Server Name', 'description' => 'This is a test server.'])->assertStatus(Response::HTTP_NO_CONTENT);
    $server = $server->refresh();
    expect($server->name)->toBe('Test Server Name');
    expect($server->description)->toBe('This is a test server.');
})->with('renamePermissionsDataProvider');
test('subuser cannot change server name without permission', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $originalName = $server->name;
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/rename", ['name' => 'Test Server Name'])->assertStatus(Response::HTTP_FORBIDDEN);
    $server = $server->refresh();
    expect($server->name)->toBe($originalName);
});
test('server can be reinstalled', function (array $permissions): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount($permissions);
    expect($server->isInstalled())->toBeTrue();
    $fake = new FakeDaemonServer;
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/reinstall")->assertStatus(Response::HTTP_ACCEPTED);
    $fake->assertReinstalled();
    $server = $server->refresh();
    expect($server->status)->toBe(Server::STATUS_INSTALLING);
})->with('reinstallPermissionsDataProvider');
test('a server that skips its install script cannot be reinstalled', function (array $permissions): void {
    [$user, $server] = $this->generateTestAccount($permissions);
    $server->update(['skip_scripts' => true]);
    $fake = new FakeDaemonServer;

    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}")->assertOk()->assertJsonPath('attributes.skip_scripts', true);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/reinstall")
        ->assertStatus(Response::HTTP_BAD_REQUEST)
        ->assertJsonPath('errors.0.detail', trans('admin/server.exceptions.skipping_install_script'));

    expect($server->refresh()->status)->toBeNull()
        ->and($fake->callsFor('reinstall'))->toBeEmpty();
})->with('reinstallPermissionsDataProvider');
test('subuser cannot reinstall server without permission', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/reinstall")->assertStatus(Response::HTTP_FORBIDDEN);
    $server = $server->refresh();
    expect($server->isInstalled())->toBeTrue();
});
test('docker image can be changed', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $images = array_values($server->egg->docker_images);
    $server->forceFill(['image' => $images[0]])->saveOrFail();
    $target = end($images);
    $this->actingAs($user)->putJson("/api/client/servers/{$server->uuid}/settings/docker-image", ['docker_image' => $target])->assertStatus(Response::HTTP_NO_CONTENT);
    expect($server->refresh()->image)->toBe($target);
});
test('docker image cannot be changed when admin set', function (): void {
    [$user, $server] = $this->generateTestAccount();
    // The server factory image is intentionally not part of any egg.
    expect(array_values($server->egg->docker_images))->not->toContain($server->image);
    $this->actingAs($user)->putJson("/api/client/servers/{$server->uuid}/settings/docker-image", ['docker_image' => array_values($server->egg->docker_images)[0]])->assertStatus(Response::HTTP_BAD_REQUEST);
});
test('docker image must be defined on the egg', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $server->forceFill(['image' => array_values($server->egg->docker_images)[0]])->saveOrFail();
    $this->actingAs($user)->putJson("/api/client/servers/{$server->uuid}/settings/docker-image", ['docker_image' => 'malicious/image:latest'])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonPath('errors.0.meta.source_field', 'docker_image');
});
test('subuser cannot change docker image without permission', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $this->actingAs($user)->putJson("/api/client/servers/{$server->uuid}/settings/docker-image", ['docker_image' => array_values($server->egg->docker_images)[0]])->assertStatus(Response::HTTP_FORBIDDEN);
});
