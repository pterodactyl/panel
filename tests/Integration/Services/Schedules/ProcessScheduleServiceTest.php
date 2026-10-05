<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Schedules\ProcessScheduleServiceTest;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Pterodactyl\Contracts\Schedules\ProcessesSchedules;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Task;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\ThrowingDispatcher;
use Throwable;
use TypeError;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
dataset('dispatchNowDataProvider', fn (): array => [[true], [false]]);
test('schedule with no tasks returns exception', function (): void {
    $server = $this->createServerModel();
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    $this->expectException(DisplayException::class);
    $this->expectExceptionMessage('Cannot process schedule for task execution: no tasks are registered.');
    getService()->process($schedule);
});
test('error during schedule data update does not persist changes', function (): void {
    $server = $this->createServerModel();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id, 'cron_minute' => 'hodor']);
    /** @var Task $task */
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1]);
    try {
        getService()->process($schedule);
        $this->fail('Expected InvalidArgumentException to be thrown.');
    } catch (InvalidArgumentException) {
    }

    $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'is_processing' => false]);
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_queued' => false]);
});
test('job can be dispatched with expected initial delay', function (bool $now): void {
    Bus::fake();
    $server = $this->createServerModel();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    /** @var Task $task */
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'time_offset' => 10, 'sequence_id' => 1]);
    getService()->process($schedule, $now);
    Bus::assertDispatched(RunTaskJob::class, function ($job) use ($now, $task): true {
        expect($job)->toBeInstanceOf(RunTaskJob::class);
        expect($job->task->id)->toBe($task->id);
        // Jobs using dispatchNow should not have a delay associated with them.
        expect($job->delay)->toBe($now ? null : 10);

        return true;
    });
    $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'is_processing' => true]);
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_queued' => true]);
})->with('dispatchNowDataProvider');
test('first sequence task is found', function (): void {
    Bus::fake();
    $server = $this->createServerModel();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    /** @var Task $task */
    $task2 = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 4]);
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 2]);
    $task3 = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 3]);
    getService()->process($schedule);
    Bus::assertDispatched(RunTaskJob::class, fn (RunTaskJob $job): bool => $task->id === $job->task->id);
    $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'is_processing' => true]);
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_queued' => true]);
    $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'is_queued' => false]);
    $this->assertDatabaseHas('tasks', ['id' => $task3->id, 'is_queued' => false]);
});
test('task dispatched now is reset properly if error is encountered', function (string $failure): void {
    $this->swap(Dispatcher::class, new ThrowingDispatcher(new $failure('Test thrown exception')));
    $server = $this->createServerModel();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id, 'last_run_at' => null]);
    /** @var Task $task */
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1]);
    try {
        getService()->process($schedule, true);
        $this->fail('Expected Exception to be thrown.');
    } catch (Throwable $throwable) {
        expect($throwable->getMessage())->toBe('Test thrown exception');
    }

    $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'is_processing' => false, 'last_run_at' => CarbonImmutable::now()->toAtomString()]);
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_queued' => false]);
})->with([Exception::class, TypeError::class]);
function getService(): ProcessesSchedules
{
    return (fn () => $this->app->make(ProcessesSchedules::class))->call(pterodactylTestCase());
}

test('batch processing uses bounded reads while claiming schedules', function (): void {
    $server = $this->createServerModel();
    $ids = [];
    for ($i = 0; $i < 10; $i++) {
        $schedule = Schedule::factory()->create(['server_id' => $server->id, 'only_when_online' => false]);
        Task::factory()->create(['schedule_id' => $schedule->id, 'sequence_id' => 1]);
        $ids[] = $schedule->id;
    }

    Bus::fake([RunTaskJob::class]);
    $schedules = Schedule::query()->whereKey($ids)->with('tasks')->get();
    DB::flushQueryLog();
    DB::enableQueryLog();
    try {
        foreach ($schedules as $schedule) {
            getService()->process($schedule);
        }

        $reads = array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'select'));
        expect(count($reads))->toBeLessThanOrEqual(20);
    } finally {
        DB::disableQueryLog();
    }

    Bus::assertDispatchedTimes(RunTaskJob::class, 10);
});

test('failed queue publication releases the schedule and preserves its retry time', function (): void {
    $this->swap(Dispatcher::class, new ThrowingDispatcher(new Exception('Unused synchronous failure')));
    $schedule = Schedule::factory()->for($this->createServerModel())->create(['next_run_at' => now()->subMinute(), 'last_run_at' => now()->subHour()]);
    $task = Task::factory()->for($schedule)->create(['sequence_id' => 1]);
    $nextRun = $schedule->next_run_at;
    $lastRun = $schedule->last_run_at;

    expect(fn () => getService()->process($schedule))->toThrow(LogicException::class, 'ThrowingDispatcher only supports dispatchNow().');

    expect($schedule->refresh()->is_processing)->toBeFalse();
    expect($task->refresh()->is_queued)->toBeFalse();
    expect($schedule->next_run_at->equalTo($nextRun))->toBeTrue();
    expect($schedule->last_run_at->equalTo($lastRun))->toBeTrue();
});

test('a stale schedule instance cannot claim an active run twice', function (): void {
    Bus::fake([RunTaskJob::class]);
    $schedule = Schedule::factory()->for($this->createServerModel())->create();
    Task::factory()->for($schedule)->create(['sequence_id' => 1]);
    $stale = $schedule->fresh();

    getService()->process($schedule);

    expect(fn () => getService()->process($stale))->toThrow(DisplayException::class, 'This schedule is already being processed.');
    Bus::assertDispatchedTimes(RunTaskJob::class, 1);
});

test('a schedule uses current task ordering instead of a previously loaded snapshot', function (): void {
    Bus::fake([RunTaskJob::class]);
    $schedule = Schedule::factory()->for($this->createServerModel())->create();
    $first = Task::factory()->for($schedule)->create(['sequence_id' => 1]);
    $second = Task::factory()->for($schedule)->create(['sequence_id' => 2]);
    $schedule->load('tasks');
    $first->update(['sequence_id' => 2]);
    $second->update(['sequence_id' => 1]);

    getService()->process($schedule);

    Bus::assertDispatched(RunTaskJob::class, fn (RunTaskJob $job): bool => $job->task->is($second));
});

test('a rolled back parent transaction never publishes the claimed schedule', function (): void {
    Bus::fake([RunTaskJob::class]);
    $schedule = Schedule::factory()->for($this->createServerModel())->create();
    $task = Task::factory()->for($schedule)->create(['sequence_id' => 1]);

    DB::beginTransaction();
    getService()->process($schedule);
    Bus::assertNothingDispatched();
    DB::rollBack();

    Bus::assertNothingDispatched();
    expect($schedule->refresh()->is_processing)->toBeFalse();
    expect($task->refresh()->is_queued)->toBeFalse();
});
