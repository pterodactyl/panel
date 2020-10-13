<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server;

use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
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
        $allocation = $this->getAllocation($server);

        $response = $this->actingAs($user)->getJson($this->link($server, '/network/allocations'));

        $response->assertOk();
        $response->assertJsonPath('object', 'list');
        $response->assertJsonCount(1, 'data');

        $this->assertJsonTransformedWith($response->json('data.0.attributes'), $allocation);
    }

    /**
     * Test that allocations cannot be returned without the required user permissions.
     */
    public function testServerAllocationsAreNotReturnedWithoutPermission()
    {
        [$user, $server] = $this->generateTestAccount();
        $user2 = factory(User::class)->create();

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
     * @param array $permissions
     * @dataProvider updatePermissionsDataProvider
     */
    public function testAllocationNotesCanBeUpdated(array $permissions)
    {
        [$user, $server] = $this->generateTestAccount($permissions);
        $allocation = $this->getAllocation($server);

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
        $user2 = factory(User::class)->create();

        $server->owner_id = $user2->id;
        $server->save();

        $this->actingAs($user)->postJson($this->link($this->getAllocation($server)))
            ->assertNotFound();

        [$user, $server] = $this->generateTestAccount([Permission::ACTION_ALLOCATION_CREATE]);

        $this->actingAs($user)->postJson($this->link($this->getAllocation($server)))
            ->assertForbidden();
    }

    /**
     * @param array $permissions
     * @dataProvider updatePermissionsDataProvider
     */
    public function testPrimaryAllocationCanBeModified(array $permissions)
    {
        [$user, $server] = $this->generateTestAccount($permissions);
        $allocation = $this->getAllocation($server);
        $allocation2 = $this->getAllocation($server);

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
        $user2 = factory(User::class)->create();

        $server->owner_id = $user2->id;
        $server->save();

        $this->actingAs($user)->postJson($this->link($this->getAllocation($server), '/primary'))
            ->assertNotFound();

        [$user, $server] = $this->generateTestAccount([Permission::ACTION_ALLOCATION_CREATE]);

        $this->actingAs($user)->postJson($this->link($this->getAllocation($server), '/primary'))
            ->assertForbidden();
    }

    /**
     * @param array $permissions
     * @dataProvider deletePermissionsDataProvider
     */
    public function testAllocationCanBeDeleted(array $permissions)
    {
        [$user, $server] = $this->generateTestAccount($permissions);
        $allocation = $this->getAllocation($server);
        $allocation2 = $this->getAllocation($server);

        $allocation2->notes = 'Filled notes';
        $allocation2->save();

        $server->allocation_id = $allocation->id;
        $server->save();

        $this->actingAs($user)->deleteJson($this->link($allocation))
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('errors.0.code', 'DisplayException')
            ->assertJsonPath('errors.0.detail', 'Cannot delete the primary allocation for a server.');

        $this->actingAs($user)->deleteJson($this->link($allocation2))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $server = $server->refresh();
        $allocation2 = $allocation2->refresh();

        $this->assertSame($allocation->id, $server->allocation_id);
        $this->assertNull($allocation2->server_id);
        $this->assertNull($allocation2->notes);
    }

    public function testAllocationCannotBeDeletedByInvalidUser()
    {
        [$user, $server] = $this->generateTestAccount();
        $user2 = factory(User::class)->create();

        $server->owner_id = $user2->id;
        $server->save();

        $this->actingAs($user)->deleteJson($this->link($this->getAllocation($server)))
            ->assertNotFound();

        [$user, $server] = $this->generateTestAccount([Permission::ACTION_ALLOCATION_CREATE]);

        $this->actingAs($user)->deleteJson($this->link($this->getAllocation($server)))
            ->assertForbidden();
    }

    public function updatePermissionsDataProvider()
    {
        return [[[]], [[Permission::ACTION_ALLOCATION_UPDATE]]];
    }

    public function deletePermissionsDataProvider()
    {
        return [[[]], [[Permission::ACTION_ALLOCATION_DELETE]]];
    }

    /**
     * @param \Pterodactyl\Models\Server $server
     * @return \Pterodactyl\Models\Allocation
     */
    protected function getAllocation(Server $server): Allocation
    {
        return factory(Allocation::class)->create([
            'server_id' => $server->id,
            'node_id' => $server->node_id,
        ]);
    }
}
