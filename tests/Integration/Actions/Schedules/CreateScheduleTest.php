<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Schedules\CreateScheduleTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Schedules\CreatesSchedules;
use Pterodactyl\Contracts\Schedules\CreatesScheduleTasks;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Models\ActivityLog;
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

    expect(ActivityLog::query()->where('event', 'server:schedule.create')->sole()->subjects()->where('subject_id', $schedule->id)->exists())->toBeTrue();
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
