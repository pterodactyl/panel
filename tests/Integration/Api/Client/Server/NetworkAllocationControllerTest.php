<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server;

use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class NetworkAllocationControllerTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that a servers allocations are returned in the expected format.
     */
    public function testServerAllocationsAreReturned()
    {
        [$user, $server] = $this->generateTestAccount();

        $response = $this->actingAs($user)->getJson($this->link($server, '/network/allocations'));

        $response->assertOk();
        $response->assertJsonPath('object', 'list');
        $response->assertJsonCount(1, 'data');

        $this->assertJsonTransformedWith($response->json('data.0.attributes'), $server->allocation);
    }

    /**
     * Test that allocations cannot be returned without the required user permissions.
     */
    public function testServerAllocationsAreNotReturnedWithoutPermission()
    {
        [$user, $server] = $this->generateTestAccount();
        $user2 = User::factory()->create();

        $server->owner_id = $user2->id;
        $server->save();

        $this->actingAs($user)->getJson($this->link($server, '/network/allocations'))
            ->assertNotFound();

        [$user, $server] = $this->generateTestAccount([Permission::ACTION_ALLOCATION_CREATE]);

        $this->actingAs($user)->getJson($this->link($server, '/network/allocations'))
            ->assertForbidden();
    }

    /**
     * Tests that notes on an allocation can be set correctly.
     *
     * @dataProvider updatePermissionsDataProvider
     */
    public function testAllocationNotesCanBeUpdated(array $permissions)
    {
        [$user, $server] = $this->generateTestAccount($permissions);
        $allocation = $server->allocation;

        $this->assertNull($allocation->notes);

        $this->actingAs($user)->postJson($this->link($allocation), [])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('errors.0.meta.rule', 'present');

        $this->actingAs($user)->postJson($this->link($allocation), ['notes' => 'Test notes'])
            ->assertOk()
            ->assertJsonPath('object', Allocation::RESOURCE_NAME)
            ->assertJsonPath('attributes.notes', 'Test notes');

        $allocation = $allocation->refresh();

        $this->assertSame('Test notes', $allocation->notes);

        $this->actingAs($user)->postJson($this->link($allocation), ['notes' => null])
            ->assertOk()
            ->assertJsonPath('object', Allocation::RESOURCE_NAME)
            ->assertJsonPath('attributes.notes', null);

        $allocation = $allocation->refresh();

        $this->assertNull($allocation->notes);
    }

    public function testAllocationNotesCannotBeUpdatedByInvalidUsers()
    {
        [$user, $server] = $this->generateTestAccount();
        $user2 = User::factory()->create();

        $server->owner_id = $user2->id;
        $server->save();

        $this->actingAs($user)->postJson($this->link($server->allocation))->assertNotFound();

        [$user, $server] = $this->generateTestAccount([Permission::ACTION_ALLOCATION_CREATE]);

        $this->actingAs($user)->postJson($this->link($server->allocation))->assertForbidden();
    }

    /**
     * @dataProvider updatePermissionsDataProvider
     */
    public function testPrimaryAllocationCanBeModified(array $permissions)
    {
        [$user, $server] = $this->generateTestAccount($permissions);
        $allocation = $server->allocation;
        $allocation2 = Allocation::factory()->create(['node_id' => $server->node_id, 'server_id' => $server->id]);

        $server->allocation_id = $allocation->id;
        $server->save();

        $this->actingAs($user)->postJson($this->link($allocation2, '/primary'))
            ->assertOk();

        $server = $server->refresh();

        $this->assertSame($allocation2->id, $server->allocation_id);
    }

    public function testPrimaryAllocationCannotBeModifiedByInvalidUser()
    {
        [$user, $server] = $this->generateTestAccount();
        $user2 = User::factory()->create();

        $server->owner_id = $user2->id;
        $server->save();

        $this->actingAs($user)->postJson($this->link($server->allocation, '/primary'))
            ->assertNotFound();

        [$user, $server] = $this->generateTestAccount([Permission::ACTION_ALLOCATION_CREATE]);

        $this->actingAs($user)->postJson($this->link($server->allocation, '/primary'))
            ->assertForbidden();
    }

    public static function updatePermissionsDataProvider(): array
    {
        return [[[]], [[Permission::ACTION_ALLOCATION_UPDATE]]];
    }
}
