<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\ScheduleTask;

use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class DeleteScheduleTaskTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that an error is returned if the schedule does not belong to the server.
     */
    public function testScheduleNotBelongingToServerReturnsError()
    {
        $server2 = $this->createServerModel();
        [$user] = $this->generateTestAccount();

        $schedule = Schedule::factory()->create(['server_id' => $server2->id]);
        $task = Task::factory()->create(['schedule_id' => $schedule->id]);

        $this->actingAs($user)->deleteJson($this->link($task))->assertNotFound();
    }

    /**
     * Test that an error is returned if the task and schedule in the URL do not line up
     * with each other.
     */
    public function testTaskBelongingToDifferentScheduleReturnsError()
    {
        [$user, $server] = $this->generateTestAccount();

        $schedule = Schedule::factory()->create(['server_id' => $server->id]);
        $schedule2 = Schedule::factory()->create(['server_id' => $server->id]);
        $task = Task::factory()->create(['schedule_id' => $schedule->id]);

        $this->actingAs($user)->deleteJson("/api/client/servers/$server->uuid/schedules/$schedule2->id/tasks/$task->id")->assertNotFound();
    }

    /**
     * Test that a user without the required permissions returns an error.
     */
    public function testUserWithoutPermissionReturnsError()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_SCHEDULE_CREATE]);

        $schedule = Schedule::factory()->create(['server_id' => $server->id]);
        $task = Task::factory()->create(['schedule_id' => $schedule->id]);

        $this->actingAs($user)->deleteJson($this->link($task))->assertForbidden();

        $user2 = User::factory()->create();

        $this->actingAs($user2)->deleteJson($this->link($task))->assertNotFound();
    }

    /**
     * Test that a schedule task is deleted and items with a higher sequence ID are decremented
     * properly in the database.
     */
    public function testScheduleTaskIsDeletedAndSubsequentTasksAreUpdated()
    {
        [$user, $server] = $this->generateTestAccount();

        $schedule = Schedule::factory()->create(['server_id' => $server->id]);
        $tasks = [
            Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1]),
            Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 2]),
            Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 3]),
            Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 4]),
        ];

        $response = $this->actingAs($user)->deleteJson($this->link($tasks[1]));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas('tasks', ['id' => $tasks[0]->id, 'sequence_id' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $tasks[2]->id, 'sequence_id' => 2]);
        $this->assertDatabaseHas('tasks', ['id' => $tasks[3]->id, 'sequence_id' => 3]);
        $this->assertDatabaseMissing('tasks', ['id' => $tasks[1]->id]);
    }
}
