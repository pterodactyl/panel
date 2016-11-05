<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
namespace Pterodactyl\Console\Commands;

use DB;
use Carbon;
use Pterodactyl\Models;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Pterodactyl\Jobs\SendScheduledTask;

class ClearTasks extends Command
{

    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:tasks:clearlog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears old log entires (> 2 months) from the last log.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $entries = Models\TaskLog::where('run_time', '<=', Carbon::now()->subHours(env('APP_CLEAR_TASKLOG', 720))->toAtomString())->get();

        $this->info(sprintf('Preparing to delete %d old task log entries.', count($entries)));
        $bar = $this->output->createProgressBar(count($entries));

        foreach ($entries as &$entry) {
            $entry->delete();
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nFinished deleting old logs.");
    }
}
