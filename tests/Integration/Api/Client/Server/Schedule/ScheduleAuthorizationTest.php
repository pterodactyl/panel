<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Schedule;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class ScheduleAuthorizationTest extends ClientApiIntegrationTestCase
{
    /**
     * Tests that a subuser with access to two servers cannot improperly access a resource
     * on Server A when providing a URL that points to Server B. This prevents a regression
     * in the code where controllers didn't properly validate that a resource was assigned
     * to the server that was also present in the URL.
     *
     * The comments within the test code itself are better at explaining exactly what is
     * being tested and protected against.
     *
     * @dataProvider methodDataProvider
     */
    public function testAccessToAServersSchedulesIsRestrictedProperly(string $method, string $endpoint)
    {
        // The API $user is the owner of $server1.
        [$user, $server1] = $this->generateTestAccount();
        // Will be a subuser of $server2.
        $server2 = $this->createServerModel();
        // And as no access to $server3.
        $server3 = $this->createServerModel();

        // Set the API $user as a subuser of server 2, but with no permissions
        // to do anything with the schedules for that server.
        Subuser::factory()->create(['server_id' => $server2->id, 'user_id' => $user->id]);

        $schedule1 = Schedule::factory()->create(['server_id' => $server1->id]);
        $schedule2 = Schedule::factory()->create(['server_id' => $server2->id]);
        $schedule3 = Schedule::factory()->create(['server_id' => $server3->id]);

        // This is the only valid call for this test, accessing the schedule for the same
        // server that the API user is the owner of.
        $response = $this->actingAs($user)->json($method, $this->link($server1, '/schedules/' . $schedule1->id . $endpoint));
        $this->assertTrue($response->status() <= 204 || $response->status() === 400 || $response->status() === 422);

        // This request fails because the schedule is valid for that server but the user
        // making the request is not authorized to perform that action.
        $this->actingAs($user)->json($method, $this->link($server2, '/schedules/' . $schedule2->id . $endpoint))->assertForbidden();

        // Both of these should report a 404 error due to the schedules being linked to
        // servers that are not the same as the server in the request, or are assigned
        // to a server for which the user making the request has no access to.
        $this->actingAs($user)->json($method, $this->link($server1, '/schedules/' . $schedule2->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server1, '/schedules/' . $schedule3->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server2, '/schedules/' . $schedule3->id . $endpoint))->assertNotFound();
        $this->actingAs($user)->json($method, $this->link($server3, '/schedules/' . $schedule3->id . $endpoint))->assertNotFound();
    }

    public static function methodDataProvider(): array
    {
        return [
            ['GET', ''],
            ['POST', ''],
            ['DELETE', ''],
            ['POST', '/execute'],
            ['POST', '/tasks'],
        ];
    }
}
