<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Schedule\ScheduleAuthorizationTest;

use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
dataset('methodDataProvider', function () {
    return [['GET', ''], ['POST', ''], ['DELETE', ''], ['POST', '/execute'], ['POST', '/tasks']];
});
test('access to a servers schedules is restricted properly', function (string $method, string $endpoint) {
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
    // server that the API user is the owner of. Each variant sends a valid body so
    // the expected status proves authorization passed, not just validation.
    $body = match (true) {
        $method === 'POST' && $endpoint === '' => ['name' => 'Test', 'minute' => '*', 'hour' => '*', 'day_of_month' => '*', 'month' => '*', 'day_of_week' => '*'],
        $method === 'POST' && $endpoint === '/tasks' => ['action' => 'command', 'payload' => 'test', 'time_offset' => 0],
        default => [],
    };
    $expected = match (true) {
        $method === 'DELETE' => 204,
        // Empty schedule: passes auth, service rejects with "no tasks are registered".
        $method === 'POST' && $endpoint === '/execute' => 400,
        default => 200,
    };
    $this->actingAs($user)->json($method, $this->link($server1, '/schedules/'.$schedule1->id.$endpoint), $body)->assertStatus($expected);
    // This request fails because the schedule is valid for that server but the user
    // making the request is not authorized to perform that action.
    $this->actingAs($user)->json($method, $this->link($server2, '/schedules/'.$schedule2->id.$endpoint))->assertForbidden();
    // Both of these should report a 404 error due to the schedules being linked to
    // servers that are not the same as the server in the request, or are assigned
    // to a server for which the user making the request has no access to.
    $this->actingAs($user)->json($method, $this->link($server1, '/schedules/'.$schedule2->id.$endpoint))->assertNotFound();
    $this->actingAs($user)->json($method, $this->link($server1, '/schedules/'.$schedule3->id.$endpoint))->assertNotFound();
    $this->actingAs($user)->json($method, $this->link($server2, '/schedules/'.$schedule3->id.$endpoint))->assertNotFound();
    $this->actingAs($user)->json($method, $this->link($server3, '/schedules/'.$schedule3->id.$endpoint))->assertNotFound();
})->with('methodDataProvider');
