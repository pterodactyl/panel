<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Database;

use Mockery;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class DatabaseAuthorizationTest extends ClientApiIntegrationTestCase
{
    /**
     * @dataProvider methodDataProvider
     */
    public function testAccessToAServersDatabasesIsRestrictedProperly(string $method, string $endpoint)
    {
        // The API $user is the owner of $server1.
        [$user, $server1] = $this->generateTestAccount();
        // Will be a subuser of $server2.
        $server2 = $this->createServerModel();
        // And as no access to $server3.
        $server3 = $this->createServerModel();

        $host = DatabaseHost::factory()->create([]);

        // Set the API $user as a subuser of server 2, but with no permissions
        // to do anything with the databases for that server.
        Subuser::factory()->create(['server_id' => $server2->id, 'user_id' => $user->id]);

        $database1 = Database::factory()->create(['server_id' => $server1->id, 'database_host_id' => $host->id]);
        $database2 = Database::factory()->create(['server_id' => $server2->id, 'database_host_id' => $host->id]);
        $database3 = Database::factory()->create(['server_id' => $server3->id, 'database_host_id' => $host->id]);

        $this->instance(DatabasePasswordService::class, $mock = Mockery::mock(DatabasePasswordService::class));
        $this->instance(DatabaseManagementService::class, $mock2 = Mockery::mock(DatabaseManagementService::class));

        if ($method === 'POST') {
            $mock->expects('handle')->andReturnUndefined();
        } else {
            $mock2->expects('delete')->andReturnUndefined();
        }

        $hashids = $this->app->make(HashidsInterface::class);
        // This is the only valid call for this test, accessing the database for the same
        // server that the API user is the owner of.
        $this->actingAs($user)->json($method, $this->link($server1, '/databases/' . $hashids->encode($database1->id) . $endpoint))
            ->assertStatus($method === 'DELETE' ? 204 : 200);

        // This request fails because the database is valid for that server but the user
        // making the request is not authorized to perform that action.
        $this->actingAs($user)->json($method, $this->link($server2, '/databases/' . $hashids->encode($database2->id) . $endpoint))->assertForbidden();

        // Both of these should report a 404 error due to the database being linked to
        // servers that are not the same as the server in the request, or are assigned
        // to a server for which the user making the request has no access to.
        $this->actingAs($user)->json($method, $this->link($server1, '/databases/' . $hashids->encode($database2->id) . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server1, '/databases/' . $hashids->encode($database3->id) . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server2, '/databases/' . $hashids->encode($database3->id) . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server3, '/databases/' . $hashids->encode($database3->id) . $endpoint))->assertNotFound();
    }

    /**
     * @return \string[][]
     */
    public function methodDataProvider(): array
    {
        return [
            ['POST', '/rotate-password'],
            ['DELETE', ''],
        ];
    }
}
