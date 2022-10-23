<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Schedule;

use Pterodactyl\Models\Task;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class GetServerSchedulesTest extends ClientApiIntegrationTestCase
{
    /**
     * Cleanup after tests run.
     */
    protected function tearDown(): void
    {
        Task::query()->forceDelete();
        Schedule::query()->forceDelete();

        parent::tearDown();
    }

    /**
     * Test that schedules for a server are returned.
     *
     * @dataProvider permissionsDataProvider
     */
    public function testServerSchedulesAreReturned(array $permissions, bool $individual)
    {
        [$user, $server] = $this->generateTestAccount($permissions);

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server->id]);
        /** @var \Pterodactyl\Models\Task $task */
        $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1, 'time_offset' => 0]);

        $response = $this->actingAs($user)
            ->getJson(
                $individual
                    ? "/api/client/servers/$server->uuid/schedules/$schedule->id"
                    : "/api/client/servers/$server->uuid/schedules"
            )
            ->assertOk();

        $prefix = $individual ? '' : 'data.0.';
        if (!$individual) {
            $response->assertJsonCount(1, 'data');
        }

        $response->assertJsonCount(1, $prefix . 'attributes.relationships.tasks.data');

        $response->assertJsonPath($prefix . 'object', Schedule::RESOURCE_NAME);
        $response->assertJsonPath($prefix . 'attributes.relationships.tasks.data.0.object', Task::RESOURCE_NAME);

        $this->assertJsonTransformedWith($response->json($prefix . 'attributes'), $schedule);
        $this->assertJsonTransformedWith($response->json($prefix . 'attributes.relationships.tasks.data.0.attributes'), $task);
    }

    /**
     * Test that a schedule belonging to another server cannot be viewed.
     */
    public function testScheduleBelongingToAnotherServerCannotBeViewed()
    {
        [$user, $server] = $this->generateTestAccount();
        $server2 = $this->createServerModel(['owner_id' => $user->id]);

        $schedule = Schedule::factory()->create(['server_id' => $server2->id]);

        $this->actingAs($user)
            ->getJson("/api/client/servers/$server->uuid/schedules/$schedule->id")
            ->assertNotFound();
    }

    /**
     * Test that a subuser without the required permissions is unable to access the schedules endpoint.
     */
    public function testUserWithoutPermissionCannotViewSchedules()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_WEBSOCKET_CONNECT]);

        $this->actingAs($user)
            ->getJson("/api/client/servers/$server->uuid/schedules")
            ->assertForbidden();

        $schedule = Schedule::factory()->create(['server_id' => $server->id]);

        $this->actingAs($user)
            ->getJson("/api/client/servers/$server->uuid/schedules/$schedule->id")
            ->assertForbidden();
    }

    public function permissionsDataProvider(): array
    {
        return [
            [[], false],
            [[], true],
            [[Permission::ACTION_SCHEDULE_READ], false],
            [[Permission::ACTION_SCHEDULE_READ], true],
        ];
    }
}
