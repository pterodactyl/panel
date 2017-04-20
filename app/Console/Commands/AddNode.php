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

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Pterodactyl\Models\Location;
use Pterodactyl\Repositories\NodeRepository;

class AddNode extends Command
{
    protected $data = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:node
                            {--name= : Name of the node.}
                            {--location= : The shortcode of the location to add this node to.}
                            {--fqdn= : The fully-qualified domain for the node.}
                            {--ssl= : Should the daemon use SSL for connections (T/F).}
                            {--memory= : The total memory available on this node for servers.}
                            {--disk= : The total disk space available on this node for servers.}
                            {--daemonBase= : The directory in which server files will be stored.}
                            {--daemonListen= : The port the daemon will listen on for connections.}
                            {--daemonSFTP= : The port to be used for SFTP conncetions to the daemon.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a new node to the system via the CLI.';

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
        $locations = Location::all(['id', 'short', 'long']);

        $this->data['name'] = (is_null($this->option('name'))) ? $this->ask('Node Name') : $this->option('name');

        if (is_null($this->option('location'))) {
            $this->table(['ID', 'Short Code', 'Description'], $locations->toArray());
            $selectedLocation = $this->anticipate('Node Location (Short Name)', $locations->pluck('short')->toArray());
        } else {
            $selectedLocation = $this->option('location');
        }

        $this->data['location_id'] = $locations->where('short', $selectedLocation)->first()->id;

        if (is_null($this->option('fqdn'))) {
            $this->line('Please enter domain name (e.g node.example.com) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node.');
            $this->data['fqdn'] = $this->ask('Fully Qualified Domain Name');
        } else {
            $this->data['fqdn'] = $this->option('fqdn');
        }

        $useSSL = (is_null($this->option('ssl'))) ? $this->confirm('Use SSL', true) : $this->option('ssl');

        $this->data['scheme'] = ($useSSL) ? 'https' : 'http';
        $this->data['memory'] = (is_null($this->option('memory'))) ? $this->ask('Total Memory (in MB)') : $this->option('memory');
        $this->data['memory_overallocate'] = 0;
        $this->data['disk'] = (is_null($this->option('disk'))) ? $this->ask('Total Disk Space (in MB)') : $this->option('disk');
        $this->data['disk_overallocate'] = 0;
        $this->data['public'] = 1;
        $this->data['daemonBase'] = (is_null($this->option('daemonBase'))) ? $this->ask('Daemon Server File Location', '/srv/daemon-data') : $this->option('daemonBase');
        $this->data['daemonListen'] = (is_null($this->option('daemonListen'))) ? $this->ask('Daemon Listening Port', 8080) : $this->option('daemonListen');
        $this->data['daemonSFTP'] = (is_null($this->option('daemonSFTP'))) ? $this->ask('Daemon SFTP Port', 2022) : $this->option('daemonSFTP');

        $repo = new NodeRepository;
        $id = $repo->create($this->data);

        $this->info('Node created with ID: ' . $id);
    }
}
