<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Schedule;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Pterodactyl\Services\Schedules\ProcessScheduleService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessRunnableCommand extends Command
{
    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var string
     */
    protected $description = 'Process schedules in the database and determine which are ready to run.';

    /**
     * @var \Pterodactyl\Services\Schedules\ProcessScheduleService
     */
    protected $processScheduleService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var string
     */
    protected $signature = 'p:schedule:process';

    /**
     * ProcessRunnableCommand constructor.
     *
     * @param \Carbon\Carbon                                                $carbon
     * @param \Pterodactyl\Services\Schedules\ProcessScheduleService        $processScheduleService
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $repository
     */
    public function __construct(
        Carbon $carbon,
        ProcessScheduleService $processScheduleService,
        ScheduleRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->carbon = $carbon;
        $this->processScheduleService = $processScheduleService;
        $this->repository = $repository;
    }

    /**
     * Handle command execution.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle()
    {
        $schedules = $this->repository->getSchedulesToProcess($this->carbon->now()->toAtomString());

        $bar = $this->output->createProgressBar(count($schedules));
        $schedules->each(function ($schedule) use ($bar) {
            if ($schedule->tasks instanceof Collection && count($schedule->tasks) > 0) {
                $this->processScheduleService->handle($schedule);

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
        });

        $this->line('');
    }
}
