<?php
/**
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

namespace Pterodactyl\Console\Commands\Location;

use Illuminate\Console\Command;
use Pterodactyl\Services\Locations\LocationCreationService;

class MakeLocationCommand extends Command
{
    /**
     * @var \Pterodactyl\Services\Locations\LocationCreationService
     */
    protected $creationService;

    /**
     * @var string
     */
    protected $signature = 'p:location:make
                            {--short= : The shortcode name of this location (ex. us1).}
                            {--long= : A longer description of this location.}';

    /**
     * @var string
     */
    protected $description = 'Creates a new location on the system via the CLI.';

    /**
     * Create a new command instance.
     *
     * @param \Pterodactyl\Services\Locations\LocationCreationService $creationService
     */
    public function __construct(LocationCreationService $creationService)
    {
        parent::__construct();

        $this->creationService = $creationService;
    }

    /**
     * Handle the command execution process.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle()
    {
        $short = $this->option('short') ?? $this->ask(trans('command/messages.location.ask_short'));
        $long = $this->option('long') ?? $this->ask(trans('command/messages.location.ask_long'));

        $location = $this->creationService->handle(compact('short', 'long'));
        $this->line(trans('command/messages.location.created', [
            'name' => $location->short,
            'id' => $location->id,
        ]));
    }
}
