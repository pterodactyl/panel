<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Allocation;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class AllocationAuthorizationTest extends ClientApiIntegrationTestCase
{
    /**
     * @dataProvider methodDataProvider
     */
    public function testAccessToAServersAllocationsIsRestrictedProperly(string $method, string $endpoint)
    {
        // The API $user is the owner of $server1.
        [$user, $server1] = $this->generateTestAccount();
        // Will be a subuser of $server2.
        $server2 = $this->createServerModel();
        // And as no access to $server3.
        $server3 = $this->createServerModel();

        // Set the API $user as a subuser of server 2, but with no permissions
        // to do anything with the allocations for that server.
        Subuser::factory()->create(['server_id' => $server2->id, 'user_id' => $user->id]);

        $allocation1 = Allocation::factory()->create(['server_id' => $server1->id, 'node_id' => $server1->node_id]);
        $allocation2 = Allocation::factory()->create(['server_id' => $server2->id, 'node_id' => $server2->node_id]);
        $allocation3 = Allocation::factory()->create(['server_id' => $server3->id, 'node_id' => $server3->node_id]);

        // This is the only valid call for this test, accessing the allocation for the same
        // server that the API user is the owner of.
        $response = $this->actingAs($user)->json($method, $this->link($server1, '/network/allocations/' . $allocation1->id . $endpoint));
        $this->assertTrue($response->status() <= 204 || $response->status() === 400 || $response->status() === 422);

        // This request fails because the allocation is valid for that server but the user
        // making the request is not authorized to perform that action.
        $this->actingAs($user)->json($method, $this->link($server2, '/network/allocations/' . $allocation2->id . $endpoint))->assertForbidden();

        // Both of these should report a 404 error due to the allocations being linked to
        // servers that are not the same as the server in the request, or are assigned
        // to a server for which the user making the request has no access to.
        $this->actingAs($user)->json($method, $this->link($server1, '/network/allocations/' . $allocation2->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server1, '/network/allocations/' . $allocation3->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server2, '/network/allocations/' . $allocation3->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server3, '/network/allocations/' . $allocation3->id . $endpoint))->assertNotFound();
    }

    public static function methodDataProvider(): array
    {
        return [
            ['POST', ''],
            ['DELETE', ''],
            ['POST', '/primary'],
        ];
    }
}
