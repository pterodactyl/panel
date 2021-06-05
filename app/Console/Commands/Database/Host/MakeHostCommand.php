<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Database\Host;

use Illuminate\Console\Command;
use Pterodactyl\Services\Databases\Hosts\HostCreationService;

class MakeHostCommand extends Command
{
    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostCreationService
     */
    protected $creationService;

    /**
     * @var string
     */
    protected $signature = 'p:database:host:make
                            {--name= : A short identifier used to distinguish this location from others.}
                            {--host= : The IP address or FQDN that should be used when attempting to connect to this MySQL.}
                            {--port= : The Port that MySQL is running on for this host.}
                            {--username= : The username of an account that has enough permissions to create new users and databases on the system.}
                            {--password= : The password to the account defined.}
                            {--node_id= : A valid Node ID.}';

    /**
     * @var string
     */
    protected $description = 'Creates a new Database Host on the system via the CLI.';

    /**
     * Handle the command execution process.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(HostCreationService $creationService)
    {
        $this->creationService = $creationService;
        
        $data['name'] = $this->option('name') ?? $this->ask('Enter a short identifier used to distinguish this location from others');
        $data['host'] = $this->option('host') ?? $this->ask('Enter the IP address or FQDN that should be used when attempting to connect to this MySQL');
        $data['port'] = $this->option('port') ?? $this->ask('Enter the Port that MySQL is running on for this host');
        $data['username'] = $this->option('username') ?? $this->ask('Enter the username of an account that has enough permissions to create new users and databases on the system');
        $data['password'] = $this->option('password') ?? $this->ask('Enter the password to the account defined');
        $data['node_id'] = $this->option('node_id') ?? $this->ask('Enter a valid Node ID');
        
        $dbhost = $this->creationService->handle($data);
        $this->line('Successfully created the Database Host ' . $data['name'] . ' on node ' . $data['node_id'] . '.');
    }
}
