<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Schedules\CreateScheduleTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Schedules\CreatesSchedules;
use Pterodactyl\Contracts\Schedules\CreatesScheduleTasks;
use Pterodactyl\Contracts\Schedules\DeletesSchedules;
use Pterodactyl\Contracts\Schedules\DeletesScheduleTasks;
use Pterodactyl\Contracts\Schedules\UpdatesSchedules;
use Pterodactyl\Contracts\Schedules\UpdatesScheduleTasks;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, string|int> */
function schedule(array $overrides = []): array
{
    return [...['name' => 'Nightly', 'minute' => '0', 'hour' => '3', 'day_of_month' => '*', 'month' => '*', 'day_of_week' => '*'], ...$overrides];
}

test('the contract accepts the flags the boolean rule allows, as an extension may pass them', function (): void {
    $schedule = $this->app->make(CreatesSchedules::class)->create($this->createServerModel(), schedule(['is_active' => 1, 'only_when_online' => '0']));

    expect($schedule->is_active)->toBeTrue()
        ->and($schedule->only_when_online)->toBeFalse();
});

test('a schedule created without the http api is validated and logged like one created through it', function (): void {
    $server = $this->createServerModel();
    $schedules = $this->app->make(CreatesSchedules::class);

    expect(fn (): Schedule => $schedules->create($server, schedule(['name' => ''])))->toThrow(ValidationException::class);

    $schedule = $schedules->create($server, schedule());

    $this->assertActivityOn('server:schedule.create', $server, $schedule);
});

test('schedule and task changes made without the http api reach the server\'s feed', function (): void {
    $server = $this->createServerModel();
    $schedule = $this->app->make(CreatesSchedules::class)->create($server, schedule());
    $task = $this->app->make(CreatesScheduleTasks::class)->create($schedule, ['action' => 'command', 'payload' => 'say hi', 'time_offset' => 0]);

    $this->app->make(UpdatesScheduleTasks::class)->update($schedule, $task, ['action' => 'command', 'payload' => 'say bye', 'time_offset' => 0]);
    $this->app->make(DeletesScheduleTasks::class)->delete($schedule, $task);
    $this->app->make(UpdatesSchedules::class)->update($schedule, schedule(['name' => 'Renamed']));
    $this->app->make(DeletesSchedules::class)->delete($schedule);

    foreach (['server:task.create', 'server:task.update', 'server:task.delete', 'server:schedule.update', 'server:schedule.delete'] as $event) {
        $this->assertActivityOn($event, $server, $schedule);
    }

    expect($server->activity()->count())->toBe(6);
});

test('task rules and the backup limit hold for every caller', function (): void {
    $server = $this->createServerModel(['backup_limit' => 0]);
    $schedule = $this->app->make(CreatesSchedules::class)->create($server, schedule());
    $tasks = $this->app->make(CreatesScheduleTasks::class);

    expect(fn () => $tasks->create($schedule, ['action' => 'power', 'payload' => 'explode', 'time_offset' => 0]))->toThrow(ValidationException::class)
        ->and(fn () => $tasks->create($schedule, ['action' => 'backup', 'time_offset' => 0]))->toThrow(HttpForbiddenException::class);

    $task = $tasks->create($schedule, ['action' => 'power', 'payload' => 'restart', 'time_offset' => '30']);

    expect($task->time_offset)->toBe(30)->and($task->sequence_id)->toBe(1);
});
