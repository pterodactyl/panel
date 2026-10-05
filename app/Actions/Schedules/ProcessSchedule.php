<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Schedules;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Schedules\ProcessesSchedules;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Jobs\Schedule\RunTaskJob;
use Pterodactyl\Models\Schedule;
use Throwable;

final readonly class ProcessSchedule implements ProcessesSchedules
{
    /**
     * Process a schedule and push the first task onto the queue worker.
     *
     * @throws Throwable
     */
    public function process(Schedule $schedule, bool $now = false): void
    {
        $run = $this->claim($schedule, $now);
        $job = $run['job'];

        try {
            if (! $this->canRun($job)) {
                $job->failed();

                return;
            }

            if ($now) {
                Bus::dispatchNow($job);

                return;
            }
        } catch (Throwable $throwable) {
            rescue(fn () => $job->failed($throwable));

            throw $throwable;
        }

        DB::afterCommit(fn () => $this->publish($run));
    }

    private function canRun(RunTaskJob $job): bool
    {
        if (! $job->task->schedule->only_when_online) {
            return true;
        }

        try {
            $details = Daemon::server($job->task->schedule->server)->details();

            return ! in_array($details['state'] ?? 'offline', ['offline', 'stopping'], true);
        } catch (DaemonConnectionException) {
            return false;
        }
    }

    /** @param array{job: RunTaskJob, next_run_at: CarbonInterface|null, last_run_at: CarbonInterface|null} $run */
    private function publish(array $run): void
    {
        try {
            Bus::dispatch($run['job']->delay($run['job']->task->time_offset));
        } catch (Throwable $throwable) {
            rescue(fn () => $this->release($run));

            throw $throwable;
        }
    }

    /** @return array{job: RunTaskJob, next_run_at: CarbonInterface|null, last_run_at: CarbonInterface|null} */
    private function claim(Schedule $schedule, bool $now): array
    {
        return DB::transaction(function () use ($schedule, $now): array {
            $claimed = Schedule::query()->without('tasks')->lockForUpdate()->findOrFail($schedule->id);
            throw_if($claimed->is_processing, DisplayException::class, 'This schedule is already being processed.');

            $task = $claimed->tasks()->orderBy('sequence_id')->first();
            throw_if($task === null, DisplayException::class, 'Cannot process schedule for task execution: no tasks are registered.');

            $run = ['job' => new RunTaskJob($task->setRelation('schedule', $claimed), $now), 'next_run_at' => $claimed->next_run_at, 'last_run_at' => $claimed->last_run_at];
            $claimed->forceFill(['is_processing' => true, 'next_run_at' => $claimed->getNextRunDate()])->saveOrFail();
            $task->update(['is_queued' => true]);

            return $run;
        });
    }

    /** @param array{job: RunTaskJob, next_run_at: CarbonInterface|null, last_run_at: CarbonInterface|null} $run */
    private function release(array $run): void
    {
        DB::transaction(function () use ($run): void {
            $schedule = Schedule::query()->without('tasks')->lockForUpdate()->findOrFail($run['job']->task->schedule_id);
            $run['job']->task->update(['is_queued' => false]);
            $schedule->forceFill([
                'is_processing' => false,
                'next_run_at' => $run['next_run_at'],
                'last_run_at' => $run['last_run_at'],
            ])->saveOrFail();
        });
    }
}
