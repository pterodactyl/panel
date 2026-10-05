<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Subuser\ExtensionSubuserPermissionsTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
beforeEach(function (): void {
    $this->app->make(ExtensionPermissionRegistry::class)->register('issue-probe', 'Control the probe.', ['view' => 'View the probe.']);
});
test('the permission list includes extension permission groups', function (): void {
    [$user] = $this->generateTestAccount();

    $permissions = $this->actingAs($user)->getJson('/api/client/permissions')->assertOk()->json('attributes.permissions');

    expect($permissions['ext.issue-probe'])->toBe(['description' => 'Control the probe.', 'keys' => ['view' => 'View the probe.']])
        ->and($permissions)->toHaveKey('control');
});
test('a subuser can be granted a registered extension permission', function (): void {
    [$user, $server] = $this->generateTestAccount();

    $this->actingAs($user)->postJson($this->link($server).'/users', [
        'email' => $email = 'probe-subuser@example.com',
        'permissions' => [Permissions::ControlConsole->value, 'ext.issue-probe.view', 'ext.unregistered.view'],
    ])->assertOk()->assertJsonPath('attributes.permissions', [Permissions::ControlConsole->value, 'ext.issue-probe.view', Permissions::WebsocketConnect->value]);

    $subuser = User::query()->where('email', $email)->firstOrFail();
    expect($subuser->can('ext.issue-probe.view', $server))->toBeTrue()
        ->and($subuser->can('ext.unregistered.view', $server))->toBeFalse();
});
