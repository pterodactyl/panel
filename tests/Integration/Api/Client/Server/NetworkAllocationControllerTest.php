<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\NetworkAllocationControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
dataset('updatePermissionsDataProvider', fn (): array => [[[]], [[Permissions::AllocationUpdate->value]]]);
test('server allocations are returned', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $response = $this->actingAs($user)->getJson($this->link($server, '/network/allocations'));
    $response->assertOk();
    $response->assertJsonPath('object', 'list');
    $response->assertJsonCount(1, 'data');

    $allocation = $server->allocation;
    expect($response->json('data.0.attributes'))->toBe([
        'id' => $allocation->id,
        'ip' => $allocation->ip,
        'ip_alias' => $allocation->ip_alias,
        'port' => $allocation->port,
        'notes' => null,
        'is_default' => true,
    ]);
});
test('server allocations are not returned without permission', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $user2 = User::factory()->create();
    $server->owner_id = $user2->id;
    $server->save();
    $this->actingAs($user)->getJson($this->link($server, '/network/allocations'))->assertNotFound();
    [$user, $server] = $this->generateTestAccount([Permissions::AllocationCreate->value]);
    $this->actingAs($user)->getJson($this->link($server, '/network/allocations'))->assertForbidden();
});
test('allocation notes can be updated', function (array $permissions): void {
    [$user, $server] = $this->generateTestAccount($permissions);
    $allocation = $server->allocation;
    expect($allocation->notes)->toBeNull();
    $this->actingAs($user)->postJson($this->link($allocation), [])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonPath('errors.0.meta.rule', 'present');
    $this->actingAs($user)->postJson($this->link($allocation), ['notes' => 'Test notes'])->assertOk()->assertJsonPath('object', Allocation::RESOURCE_NAME)->assertJsonPath('attributes.notes', 'Test notes');
    $allocation = $allocation->refresh();
    expect($allocation->notes)->toBe('Test notes');
    $this->actingAs($user)->postJson($this->link($allocation), ['notes' => null])->assertOk()->assertJsonPath('object', Allocation::RESOURCE_NAME)->assertJsonPath('attributes.notes', null);
    $allocation = $allocation->refresh();
    expect($allocation->notes)->toBeNull();
})->with('updatePermissionsDataProvider');
test('allocation notes cannot be updated by invalid users', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $user2 = User::factory()->create();
    $server->owner_id = $user2->id;
    $server->save();
    $this->actingAs($user)->postJson($this->link($server->allocation))->assertNotFound();
    [$user, $server] = $this->generateTestAccount([Permissions::AllocationCreate->value]);
    $this->actingAs($user)->postJson($this->link($server->allocation))->assertForbidden();
});
test('primary allocation can be modified', function (array $permissions): void {
    [$user, $server] = $this->generateTestAccount($permissions);
    $allocation = $server->allocation;
    $allocation2 = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => $server->id]);
    $server->allocation_id = $allocation->id;
    $server->save();
    $this->actingAs($user)->postJson($this->link($allocation2, '/primary'))->assertOk();
    $server = $server->refresh();
    expect($server->allocation_id)->toBe($allocation2->id);
})->with('updatePermissionsDataProvider');
test('primary allocation cannot be modified by invalid user', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $user2 = User::factory()->create();
    $server->owner_id = $user2->id;
    $server->save();
    $this->actingAs($user)->postJson($this->link($server->allocation, '/primary'))->assertNotFound();
    [$user, $server] = $this->generateTestAccount([Permissions::AllocationCreate->value]);
    $this->actingAs($user)->postJson($this->link($server->allocation, '/primary'))->assertForbidden();
});
