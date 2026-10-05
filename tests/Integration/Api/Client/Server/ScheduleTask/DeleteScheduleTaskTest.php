<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\ScheduleTask\DeleteScheduleTaskTest;

use Illuminate\Http\Response;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
test('schedule not belonging to server returns error', function () {
    $server2 = $this->createServerModel();
    [$user] = $this->generateTestAccount();
    $schedule = Schedule::factory()->create(['server_id' => $server2->id]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id]);
    $this->actingAs($user)->deleteJson($this->link($task))->assertNotFound();
});
test('task belonging to different schedule returns error', function () {
    [$user, $server] = $this->generateTestAccount();
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $schedule2 = Schedule::factory()->create(['server_id' => $server->id]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id]);
    $this->actingAs($user)->deleteJson("/api/client/servers/{$server->uuid}/schedules/{$schedule2->id}/tasks/{$task->id}")->assertNotFound();
});
test('user without permission returns error', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleCreate->value]);
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id]);
    $this->actingAs($user)->deleteJson($this->link($task))->assertForbidden();
    $user2 = User::factory()->create();
    $this->actingAs($user2)->deleteJson($this->link($task))->assertNotFound();
});
test('schedule task is deleted and subsequent tasks are updated', function () {
    [$user, $server] = $this->generateTestAccount();
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $tasks = [Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1]), Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 2]), Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 3]), Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 4])];
    $response = $this->actingAs($user)->deleteJson($this->link($tasks[1]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseHas('tasks', ['id' => $tasks[0]->id, 'sequence_id' => 1]);
    $this->assertDatabaseHas('tasks', ['id' => $tasks[2]->id, 'sequence_id' => 2]);
    $this->assertDatabaseHas('tasks', ['id' => $tasks[3]->id, 'sequence_id' => 3]);
    $this->assertDatabaseMissing('tasks', ['id' => $tasks[1]->id]);
});
