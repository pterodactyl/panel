<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Schedule;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Contracts\Schedules\ProcessesSchedules;
use Pterodactyl\Models\Schedule;
use Throwable;

#[Description('Process schedules in the database and determine which are ready to run.')]
#[Signature('p:schedule:process')]
class ProcessRunnableCommand extends Command
{
    /**
     * Handle command execution.
     */
    public function handle(): int
    {
        $schedules = Schedule::query()
            ->with(['tasks', 'server.node'])
            ->whereRelation('server', fn (Builder $builder) => $builder->whereNull('status'))
            ->where('is_active', true)
            ->where('is_processing', false)
            ->whereRaw('next_run_at <= NOW()')
            ->get();

        if ($schedules->count() < 1) {
            $this->line('There are no scheduled tasks for servers that need to be run.');

            return 0;
        }

        $bar = $this->output->createProgressBar(count($schedules));
        foreach ($schedules as $schedule) {
            $bar->clear();
            $this->processSchedule($schedule);
            $bar->advance();
            $bar->display();
        }

        $this->line('');

        return 0;
    }

    /**
     * Processes a given schedule and logs and errors encountered the console output. This should
     * never throw an exception out, otherwise you'll end up killing the entire run group causing
     * any other schedules to not process correctly.
     *
     * @see https://github.com/pterodactyl/panel/issues/2609
     */
    protected function processSchedule(Schedule $schedule): void
    {
        if ($schedule->tasks->isEmpty()) {
            return;
        }

        try {
            $this->getLaravel()->make(ProcessesSchedules::class)->process($schedule);

            $this->line(trans('command/messages.schedule.output_line', [
                'schedule' => $schedule->name,
                'hash' => $schedule->hashid,
            ]));
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage(), ['exception' => $throwable, 'schedule_id' => $schedule->id]);

            $this->error("An error was encountered while processing Schedule #$schedule->id: ".$throwable->getMessage());
        }
    }
}
