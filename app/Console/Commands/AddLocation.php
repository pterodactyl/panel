<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>.
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

use Illuminate\Console\Command;
use Pterodactyl\Repositories\LocationRepository;

class AddLocation extends Command
{
    protected $data = [];

     /**
      * The name and signature of the console command.
      *
      * @var string
      */
     protected $signature = 'pterodactyl:location
                             {--short= : The shortcode name of this location (ex. us1).}
                             {--long= : A longer description of this location.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new location on the system via the CLI.';

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
        $this->data['short'] = (is_null($this->option('short'))) ? $this->ask('Location Short Code') : $this->option('short');
        $this->data['long'] = (is_null($this->option('long'))) ? $this->ask('Location Description') : $this->option('long');

        $repo = new LocationRepository;
        $id = $repo->create($this->data);

        $this->info('Location ' . $this->data['short'] . ' created with ID: ' . $id);
    }
}
