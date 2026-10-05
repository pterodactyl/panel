<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\ScheduleTask\UpdateScheduleTaskTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
test('task can be updated', function () {
    [$user, $server] = $this->generateTestAccount();
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1, 'action' => 'command', 'payload' => 'say Old', 'time_offset' => 0]);
    $this->actingAs($user)->postJson($this->link($task), ['action' => 'command', 'payload' => 'say Updated', 'time_offset' => 30])->assertOk()->assertJsonPath('attributes.payload', 'say Updated')->assertJsonPath('attributes.time_offset', 30);
    $task->refresh();
    expect($task->payload)->toBe('say Updated');
    expect($task->time_offset)->toBe(30);
});
test('updating sequence reorders other tasks', function () {
    [$user, $server] = $this->generateTestAccount();
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $tasks = collect([1, 2, 3])->map(fn (int $i) => Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => $i, 'action' => 'command', 'payload' => "say {$i}", 'time_offset' => 0]));
    // Move the last task to the front; the other two shift up by one.
    $this->actingAs($user)->postJson($this->link($tasks[2]), ['action' => 'command', 'payload' => 'say 3', 'time_offset' => 0, 'sequence_id' => 1])->assertOk()->assertJsonPath('attributes.sequence_id', 1);
    expect($tasks[0]->refresh()->sequence_id)->toBe(2);
    expect($tasks[1]->refresh()->sequence_id)->toBe(3);
    expect($tasks[2]->refresh()->sequence_id)->toBe(1);
});
test('backup task cannot be set when backups are disabled', function () {
    [$user, $server] = $this->generateTestAccount();
    $server->update(['backup_limit' => 0]);
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1, 'action' => 'command', 'payload' => 'say Test', 'time_offset' => 0]);
    $this->actingAs($user)->postJson($this->link($task), ['action' => 'backup', 'time_offset' => 0])->assertForbidden();
});
test('task belonging to another schedule is not found', function () {
    [$user, $server] = $this->generateTestAccount();
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $otherSchedule = Schedule::factory()->create(['server_id' => $server->id]);
    $task = Task::factory()->create(['schedule_id' => $otherSchedule->id, 'sequence_id' => 1, 'action' => 'command', 'payload' => 'say Test', 'time_offset' => 0]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}/tasks/{$task->id}", ['action' => 'command', 'payload' => 'say Test', 'time_offset' => 0])->assertNotFound();
});
test('a subuser with the action permission can change a task', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleUpdate->value, Permissions::ControlConsole->value]);
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1, 'action' => 'power', 'payload' => 'start', 'time_offset' => 0]);
    $this->actingAs($user)->postJson($this->link($task), ['action' => 'command', 'payload' => 'say Test', 'time_offset' => 10])->assertOk();
    $task->refresh();
    expect($task->action)->toBe('command');
    expect($task->payload)->toBe('say Test');
});
test('a task cannot be changed to an action the subuser lacks permission for', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleUpdate->value]);
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1, 'action' => 'power', 'payload' => 'start', 'time_offset' => 0]);
    $this->actingAs($user)->postJson($this->link($task), ['action' => 'command', 'payload' => 'say Test', 'time_offset' => 10])->assertForbidden();
    $task->refresh();
    expect($task->action)->toBe('power');
    expect($task->payload)->toBe('start');
});
test('an updated power task only accepts known signals', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleUpdate->value, Permissions::ControlStart->value]);
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1, 'action' => 'power', 'payload' => 'start', 'time_offset' => 0]);
    $this->actingAs($user)->postJson($this->link($task), ['action' => 'power', 'payload' => 'invalid', 'time_offset' => 0])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.rule', 'in')
        ->assertJsonPath('errors.0.meta.source_field', 'payload');
});
test('subuser requires schedule update permission', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleRead->value]);
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1, 'action' => 'command', 'payload' => 'say Test', 'time_offset' => 0]);
    $this->actingAs($user)->postJson($this->link($task), ['action' => 'command', 'payload' => 'say Test', 'time_offset' => 0])->assertForbidden();
});
