<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Jobs\Schedule\RunTaskJobTest;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use LogicException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Task;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonPower;
use Pterodactyl\Tests\Support\Fakes\ThrowingDispatcher;
use TypeError;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
dataset('isManualRunDataProvider', fn (): array => [[true], [false]]);
test('inactive job is not run', function (): void {
    $server = $this->createServerModel();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id, 'is_processing' => true, 'last_run_at' => null, 'is_active' => false]);
    /** @var Task $task */
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'is_queued' => true]);
    $job = new RunTaskJob($task);
    Bus::dispatchSync($job);
    $task->refresh();
    $schedule->refresh();
    expect($task->is_queued)->toBeFalse();
    expect($schedule->is_processing)->toBeFalse();
    expect($schedule->is_active)->toBeFalse();
    expect(CarbonImmutable::now()->isSameAs(DateTimeInterface::ATOM, $schedule->last_run_at))->toBeTrue();
});
test('job with invalid action throws exception', function (): void {
    $server = $this->createServerModel();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    /** @var Task $task */
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'action' => 'foobar']);
    $job = new RunTaskJob($task);
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid task action provided: foobar');
    Bus::dispatchSync($job);
});
test('job is executed', function (bool $isManualRun): void {
    $server = $this->createServerModel();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id, 'is_active' => ! $isManualRun, 'is_processing' => true, 'last_run_at' => null]);
    /** @var Task $task */
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'action' => Task::ACTION_POWER, 'payload' => 'start', 'is_queued' => true, 'continue_on_failure' => false]);
    $fake = new FakeDaemonPower;
    Bus::dispatchSync(new RunTaskJob($task, $isManualRun));
    $fake->assertSent('start');
    $task->refresh();
    $schedule->refresh();
    expect($task->is_queued)->toBeFalse();
    expect($schedule->is_processing)->toBeFalse();
    expect(CarbonImmutable::now()->isSameAs(DateTimeInterface::ATOM, $schedule->last_run_at))->toBeTrue();
})->with('isManualRunDataProvider');
test('exception during run is handled correctly', function (bool $continueOnFailure): void {
    $server = $this->createServerModel();
    /** @var Schedule $schedule */
    $schedule = Schedule::factory()->create(['server_id' => $server->id]);
    /** @var Task $task */
    $task = Task::factory()->create(['schedule_id' => $schedule->id, 'action' => Task::ACTION_POWER, 'payload' => 'start', 'continue_on_failure' => $continueOnFailure]);
    $fake = new FakeDaemonPower;
    $fake->throwable = new DaemonConnectionException(Http::failedRequest([], 200));
    if (! $continueOnFailure) {
        $this->expectException(DaemonConnectionException::class);
    }

    Bus::dispatchSync(new RunTaskJob($task));
    $fake->assertSent('start');
    if ($continueOnFailure) {
        $task->refresh();
        $schedule->refresh();
        expect($task->is_queued)->toBeFalse();
        expect($schedule->is_processing)->toBeFalse();
        expect(CarbonImmutable::now()->isSameAs(DateTimeInterface::ATOM, $schedule->last_run_at))->toBeTrue();
    }
})->with('isManualRunDataProvider');
test('task is not run if server is suspended', function (): void {
    $server = $this->createServerModel(['status' => Server::STATUS_SUSPENDED]);
    $schedule = Schedule::factory()->for($server)->create(['last_run_at' => Date::now()->subHour()]);
    $task = Task::factory()->for($schedule)->create(['action' => Task::ACTION_POWER, 'payload' => 'start']);
    Bus::dispatchSync(new RunTaskJob($task));
    $task->refresh();
    $schedule->refresh();
    expect($task->is_queued)->toBeFalse();
    expect($schedule->is_processing)->toBeFalse();
    expect(Date::now()->isSameAs(DateTimeInterface::ATOM, $schedule->last_run_at))->toBeTrue();
});

test('failure cleanup accepts PHP errors and clears every queued task in the run', function (): void {
    $schedule = Schedule::factory()->for($this->createServerModel())->create(['is_processing' => true]);
    $task = Task::factory()->for($schedule)->create(['is_queued' => true, 'sequence_id' => 1]);
    $next = Task::factory()->for($schedule)->create(['is_queued' => true, 'sequence_id' => 2]);

    (new RunTaskJob($task))->failed(new TypeError('Task execution failed'));

    expect($task->refresh()->is_queued)->toBeFalse();
    expect($next->refresh()->is_queued)->toBeFalse();
    expect($schedule->refresh()->is_processing)->toBeFalse();
});

test('failed next task publication terminates the queue job without retrying the completed command', function (): void {
    $schedule = Schedule::factory()->for($this->createServerModel())->create(['is_processing' => true]);
    $task = Task::factory()->for($schedule)->create(['action' => Task::ACTION_POWER, 'payload' => 'start', 'is_queued' => true, 'sequence_id' => 1]);
    $next = Task::factory()->for($schedule)->create(['sequence_id' => 2]);
    $power = new FakeDaemonPower;
    $this->swap(Dispatcher::class, new ThrowingDispatcher(new TypeError('Unused synchronous failure')));
    $job = (new RunTaskJob($task))->withFakeQueueInteractions();

    $this->app->call($job->handle(...));

    $job->assertFailedWith(LogicException::class);
    $power->assertSentTimes('start', 1);
    expect($task->refresh()->is_queued)->toBeFalse();
    expect($next->refresh()->is_queued)->toBeFalse();
    expect($schedule->refresh()->is_processing)->toBeFalse();
});
