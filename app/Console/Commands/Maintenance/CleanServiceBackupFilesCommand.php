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

namespace Pterodactyl\Console\Commands\Maintenance;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class CleanServiceBackupFilesCommand extends Command
{
    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var string
     */
    protected $description = 'Clean orphaned .bak files created when modifying services.';

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $disk;

    /**
     * @var string
     */
    protected $signature = 'p:maintenance:clean-service-backups';

    /**
     * CleanServiceBackupFilesCommand constructor.
     *
     * @param \Carbon\Carbon                           $carbon
     * @param \Illuminate\Contracts\Filesystem\Factory $filesystem
     */
    public function __construct(Carbon $carbon, FilesystemFactory $filesystem)
    {
        parent::__construct();

        $this->carbon = $carbon;
        $this->disk = $filesystem->disk();
    }

    /**
     * Handle command execution.
     */
    public function handle()
    {
        $files = $this->disk->files('services/.bak');

        collect($files)->each(function ($file) {
            $lastModified = $this->carbon->timestamp($this->disk->lastModified($file));
            if ($lastModified->diffInMinutes($this->carbon->now()) > 5) {
                $this->disk->delete($file);
                $this->info(trans('command/messages.maintenance.deleting_service_backup', ['file' => $file]));
            }
        });
    }
}
