<?php

namespace Pterodactyl\Console\Commands\Schedule;

use Illuminate\Console\Command;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Services\Schedules\ProcessScheduleService;

class ProcessRunnableCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'p:schedule:process';

    /**
     * @var string
     */
    protected $description = 'Process schedules in the database and determine which are ready to run.';

    /**
     * Handle command execution.
     *
     * @param \Pterodactyl\Services\Schedules\ProcessScheduleService $service
     *
     * @throws \Throwable
     */
    public function handle(ProcessScheduleService $service)
    {
        $schedules = Schedule::query()->with('tasks')
            ->where('is_active', true)
            ->where('is_processing', false)
            ->whereRaw('next_run_at <= NOW()')
            ->get();

        if ($schedules->count() < 1) {
            $this->line('There are no scheduled tasks for servers that need to be run.');

            return;
        }

        $bar = $this->output->createProgressBar(count($schedules));
        foreach ($schedules as $schedule) {
            if ($schedule->tasks->isNotEmpty()) {
                $service->handle($schedule);

                if ($this->input->isInteractive()) {
                    $bar->clear();
                    $this->line(trans('command/messages.schedule.output_line', [
                        'schedule' => $schedule->name,
                        'hash' => $schedule->hashid,
                    ]));
                }
            }

            $bar->advance();
            $bar->display();
        }

        $this->line('');
    }
}
