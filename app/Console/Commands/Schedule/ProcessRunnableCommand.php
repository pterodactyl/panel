<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
        foreach ($schedules as $schedule) {
            if (! $schedule->tasks instanceof Collection || count($schedule->tasks) < 1) {
                $bar->advance();

                return;
            }

            $this->processScheduleService->handle($schedule);
            if ($this->input->isInteractive()) {
                $this->line(trans('command/messages.schedule.output_line', [
                    'schedule' => $schedule->name,
                    'hash' => $schedule->hashid,
                ]));
            }

            $bar->advance();
        }

        $this->line('');
    }
}
