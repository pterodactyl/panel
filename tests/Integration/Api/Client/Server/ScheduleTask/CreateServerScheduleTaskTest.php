<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\ScheduleTask;

use Pterodactyl\Models\Task;
use Illuminate\Http\Response;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class CreateServerScheduleTaskTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that a task can be created.
     *
     * @param array $permissions
     * @dataProvider permissionsDataProvider
     */
    public function testTaskCanBeCreated($permissions)
    {
        [$user, $server] = $this->generateTestAccount($permissions);

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server->id]);
        $this->assertEmpty($schedule->tasks);

        $response = $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), [
            'action' => 'command',
            'payload' => 'say Test',
            'time_offset' => 10,
            'sequence_id' => 1,
        ]);

        $response->assertOk();
        /** @var \Pterodactyl\Models\Task $task */
        $task = Task::query()->findOrFail($response->json('attributes.id'));

        $this->assertSame($schedule->id, $task->schedule_id);
        $this->assertSame(1, $task->sequence_id);
        $this->assertSame('command', $task->action);
        $this->assertSame('say Test', $task->payload);
        $this->assertSame(10, $task->time_offset);
        $this->assertJsonTransformedWith($response->json('attributes'), $task);
    }

    /**
     * Test that validation errors are returned correctly if bad data is passed into the API.
     */
    public function testValidationErrorsAreReturned()
    {
        [$user, $server] = $this->generateTestAccount();

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server->id]);

        $response = $this->actingAs($user)->postJson($this->link($schedule, '/tasks'))->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        foreach (['action', 'payload', 'time_offset'] as $i => $field) {
            $response->assertJsonPath("errors.{$i}.meta.rule", $field === 'payload' ? 'required_unless' : 'required');
            $response->assertJsonPath("errors.{$i}.meta.source_field", $field);
        }

        $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), [
            'action' => 'hodor',
            'payload' => 'say Test',
            'time_offset' => 0,
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('errors.0.meta.rule', 'in')
            ->assertJsonPath('errors.0.meta.source_field', 'action');

        $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), [
            'action' => 'command',
            'time_offset' => 0,
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('errors.0.meta.rule', 'required_unless')
            ->assertJsonPath('errors.0.meta.source_field', 'payload');

        $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), [
            'action' => 'command',
            'payload' => 'say Test',
            'time_offset' => 0,
            'sequence_id' => 'hodor',
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('errors.0.meta.rule', 'numeric')
            ->assertJsonPath('errors.0.meta.source_field', 'sequence_id');
    }

    /**
     * Test that backups can not be tasked when the backup limit is 0.
     */
    public function testBackupsCanNotBeTaskedIfLimit0()
    {
        [$user, $server] = $this->generateTestAccount();

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server->id]);

        $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), [
            'action' => 'backup',
            'time_offset' => 0,
        ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('errors.0.detail', 'A backup task cannot be created when the server\'s backup limit is set to 0.');

        $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), [
            'action' => 'backup',
            'payload' => "file.txt\nfile2.log",
            'time_offset' => 0,
        ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('errors.0.detail', 'A backup task cannot be created when the server\'s backup limit is set to 0.');
    }

    /**
     * Test that an error is returned if the user attempts to create an additional task that
     * would put the schedule over the task limit.
     */
    public function testErrorIsReturnedIfTooManyTasksExistForSchedule()
    {
        config()->set('pterodactyl.client_features.schedules.per_schedule_task_limit', 2);

        [$user, $server] = $this->generateTestAccount();

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server->id]);
        Task::factory()->times(2)->create(['schedule_id' => $schedule->id]);

        $this->actingAs($user)->postJson($this->link($schedule, '/tasks'), [
            'action' => 'command',
            'payload' => 'say test',
            'time_offset' => 0,
        ])
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('errors.0.code', 'ServiceLimitExceededException')
            ->assertJsonPath('errors.0.detail', 'Schedules may not have more than 2 tasks associated with them. Creating this task would put this schedule over the limit.');
    }

    /**
     * Test that an error is returned if the targeted schedule does not belong to the server
     * in the request.
     */
    public function testErrorIsReturnedIfScheduleDoesNotBelongToServer()
    {
        [$user, $server] = $this->generateTestAccount();
        [, $server2] = $this->generateTestAccount(['user_id' => $user->id]);

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server2->id]);

        $this->actingAs($user)
            ->postJson("/api/client/servers/{$server->uuid}/schedules/{$schedule->id}/tasks")
            ->assertNotFound();
    }

    /**
     * Test that an error is returned if the subuser making the request does not have permission
     * to update a schedule.
     */
    public function testErrorIsReturnedIfSubuserDoesNotHaveScheduleUpdatePermissions()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_SCHEDULE_CREATE]);

        /** @var \Pterodactyl\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server->id]);

        $this->actingAs($user)
            ->postJson($this->link($schedule, '/tasks'))
            ->assertForbidden();
    }

    public function permissionsDataProvider(): array
    {
        return [[[]], [[Permission::ACTION_SCHEDULE_UPDATE]]];
    }
}
