<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Allocation\CreateNewAllocationTest;

use Illuminate\Http\Response;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
/**
 * Setup tests.
 */
beforeEach(function (): void {
    config()->set('pterodactyl.client_features.allocations.enabled', true);
    config()->set('pterodactyl.client_features.allocations.range_start', 5000);
    config()->set('pterodactyl.client_features.allocations.range_end', 5050);
});
dataset('permissionDataProvider', fn (): array => [[[Permissions::AllocationCreate->value]], [[]]]);
test('new allocation can be assigned to server', function (array $permission): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount($permission);
    $server->update(['allocation_limit' => 2]);
    $response = $this->actingAs($user)->postJson($this->link($server, '/network/allocations'));
    $response->assertJsonPath('object', Allocation::RESOURCE_NAME);

    $matched = Allocation::query()->findOrFail($response->json('attributes.id'));
    expect($matched->server_id)->toBe($server->id);
    expect($response->json('attributes'))->toBe([
        'id' => $matched->id,
        'ip' => $matched->ip,
        'ip_alias' => $matched->ip_alias,
        'port' => $matched->port,
        'notes' => null,
        'is_default' => false,
    ]);
})->with('permissionDataProvider');
test('allocation cannot be created if user does not have permission', function (): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount([Permissions::AllocationUpdate->value]);
    $server->update(['allocation_limit' => 2]);
    $this->actingAs($user)->postJson($this->link($server, '/network/allocations'))->assertForbidden();
});
test('allocation cannot be created if not enabled', function (): void {
    config()->set('pterodactyl.client_features.allocations.enabled', false);
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount();
    $server->update(['allocation_limit' => 2]);
    $this->actingAs($user)->postJson($this->link($server, '/network/allocations'))->assertStatus(Response::HTTP_BAD_REQUEST)->assertJsonPath('errors.0.code', 'AutoAllocationNotEnabledException')->assertJsonPath('errors.0.detail', 'Server auto-allocation is not enabled for this instance.');
});
test('allocation cannot be created if server is at limit', function (): void {
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount();
    $server->update(['allocation_limit' => 1]);
    $this->actingAs($user)->postJson($this->link($server, '/network/allocations'))->assertStatus(Response::HTTP_BAD_REQUEST)->assertJsonPath('errors.0.code', 'DisplayException')->assertJsonPath('errors.0.detail', 'Cannot assign additional allocations to this server: limit has been reached.');
});
