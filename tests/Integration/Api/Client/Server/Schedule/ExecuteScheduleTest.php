<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Schedule\ExecuteScheduleTest;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
dataset('permissionsDataProvider', function () {
    return [[[]], [[Permissions::ScheduleUpdate->value]]];
});
test('schedule is executed right away', function (array $permissions) {
    [$user, $server] = $this->generateTestAccount($permissions);
    Bus::fake();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $response = $this->actingAs($user)->postJson($this->link($schedule, '/execute'));
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'DisplayException');
    $response->assertJsonPath('errors.0.detail', 'Cannot process schedule for task execution: no tasks are registered.');
    /** @var Task $task */
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1, 'time_offset' => 2]);
    $this->actingAs($user)->postJson($this->link($schedule, '/execute'))->assertStatus(Response::HTTP_ACCEPTED);
    Bus::assertDispatched(function (RunTaskJob $job) use ($task) {
        // A task executed right now should not have any job delay associated with it.
        expect($job->delay)->toBeNull();
        expect($job->task->id)->toBe($task->id);

        return true;
    });
})->with('permissionsDataProvider');
test('user without schedule update permission cannot execute', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ScheduleCreate->value]);
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $this->actingAs($user)->postJson($this->link($schedule, '/execute'))->assertForbidden();
});
