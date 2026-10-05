<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\ScheduleTask\CreateServerScheduleTaskTest;

use Illuminate\Http\Response;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
dataset('permissionsDataProvider', fn (): array => [[[]], [[Permissions::ScheduleUpdate->value, Permissions::ControlConsole->value]]]);
dataset('taskActionPermissions', [
    'command' => ['command', 'say Test', Permissions::ControlConsole],
    'power start' => ['power', 'start', Permissions::ControlStart],
    'power stop' => ['power', 'stop', Permissions::ControlStop],
    'power restart' => ['power', 'restart', Permissions::ControlRestart],
    'power kill' => ['power', 'kill', Permissions::ControlStop],
    'backup' => ['backup', null, Permissions::BackupCreate],
]);
test('task can be created', function (array $permissions): void {
    [$user, $server] = $this->generateTestAccount($permissions);
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $response = $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => 'command', 'payload' => 'say Test', 'time_offset' => 10, 'sequence_id' => 1]);
    $response->assertOk();
    /** @var Task $task */
    $task = Task::query()->findOrFail($response->json('attributes.id'));
    expect($task->schedule_id)->toBe($schedule->id);
    expect($task->sequence_id)->toBe(1);
    expect($task->action)->toBe('command');
    expect($task->payload)->toBe('say Test');
    expect($task->time_offset)->toBe(10);
    expect($response->json('attributes'))->toBe([
        'id' => $task->id,
        'sequence_id' => $task->sequence_id,
        'action' => $task->action,
        'payload' => $task->payload,
        'time_offset' => $task->time_offset,
        'is_queued' => $task->is_queued,
        'continue_on_failure' => $task->continue_on_failure,
        'created_at' => $task->created_at->toAtomString(),
        'updated_at' => $task->updated_at->toAtomString(),
    ]);
})->with('permissionsDataProvider');
test('validation errors are returned', function (): void {
    [$user, $server] = $this->generateTestAccount();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $response = $this->actingAs($user)->postJson($this->link($schedule, '/tasks'))->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    foreach (['action', 'payload', 'time_offset'] as $i => $field) {
        $response->assertJsonPath("errors.{$i}.meta.rule", $field === 'payload' ? 'required_unless' : 'required');
        $response->assertJsonPath("errors.{$i}.meta.source_field", $field);
    }

    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => 'hodor', 'payload' => 'say Test', 'time_offset' => 0])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonPath('errors.0.meta.rule', 'in')->assertJsonPath('errors.0.meta.source_field', 'action');
    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => 'command', 'time_offset' => 0])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonPath('errors.0.meta.rule', 'required_unless')->assertJsonPath('errors.0.meta.source_field', 'payload');
    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => 'command', 'payload' => 'say Test', 'time_offset' => 0, 'sequence_id' => 'hodor'])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonPath('errors.0.meta.rule', 'numeric')->assertJsonPath('errors.0.meta.source_field', 'sequence_id');
});
test('backups can not be tasked if limit0', function (): void {
    [$user, $server] = $this->generateTestAccount();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => 'backup', 'time_offset' => 0])->assertStatus(Response::HTTP_FORBIDDEN)->assertJsonPath('errors.0.detail', "A backup task cannot be created when the server's backup limit is set to 0.");
    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => 'backup', 'payload' => "file.txt\nfile2.log", 'time_offset' => 0])->assertStatus(Response::HTTP_FORBIDDEN)->assertJsonPath('errors.0.detail', "A backup task cannot be created when the server's backup limit is set to 0.");
});
test('a task cannot be created without the permission its action needs', function (string $action, ?string $payload): void {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleUpdate->value]);
    $server->forceFill(['backup_limit' => 1])->save();
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => $action, 'payload' => $payload, 'time_offset' => 0])->assertForbidden();
    expect($schedule->tasks()->count())->toBe(0);
})->with('taskActionPermissions');
test('a task can be created with the permission its action needs', function (string $action, ?string $payload, Permissions $permission): void {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleUpdate->value, $permission->value]);
    $server->forceFill(['backup_limit' => 1])->save();
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => $action, 'payload' => $payload, 'time_offset' => 0])->assertOk();
    $this->assertDatabaseHas('tasks', ['schedule_id' => $schedule->id, 'action' => $action, 'payload' => $payload ?? '']);
})->with('taskActionPermissions');
test('a power task only accepts known signals', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleUpdate->value, Permissions::ControlStart->value]);
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => 'power', 'payload' => 'invalid', 'time_offset' => 0])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('errors.0.meta.rule', 'in')
        ->assertJsonPath('errors.0.meta.source_field', 'payload');
});
test('error is returned if too many tasks exist for schedule', function (): void {
    config()->set('pterodactyl.client_features.schedules.per_schedule_task_limit', 2);
    [$user, $server] = $this->generateTestAccount();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    Task::factory()->times(2)->create(['schedule_id' => $schedule->id]);
    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), ['action' => 'command', 'payload' => 'say test', 'time_offset' => 0])->assertStatus(Response::HTTP_BAD_REQUEST)->assertJsonPath('errors.0.code', 'ServiceLimitExceededException')->assertJsonPath('errors.0.detail', 'Schedules may not have more than 2 tasks associated with them. Creating this task would put this schedule over the limit.');
});
test('error is returned if schedule does not belong to server', function (): void {
    [$user, $server] = $this->generateTestAccount();
    $server2 = $this->createServerModel(['owner_id' => $user->id]);
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server2->id]);
    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}/tasks")->assertNotFound();
});
test('error is returned if subuser does not have schedule update permissions', function (): void {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleCreate->value]);
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->postJson($this->link($schedule, '/tasks'))->assertForbidden();
});
