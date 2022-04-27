<?php

/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Database;

use Illuminate\Console\Command;
use Pterodactyl\Services\Databases\Hosts\HostCreationService;

class MakeDatabaseHostCommand extends Command
{
    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostCreationService
     */
    protected $creationService;

    /**
     * @var string
     */
    protected $signature = 'p:databasehost:make
                            {--name= : A name to identify the database.}
                            {--host= : The host address of the database host.}
                            {--port= : The host port of the database host (default: 3306).}
                            {--username= : The username with grant privileges to host.}
                            {--password= : The password of username.}
                            {--nodeId= : A valid nodeId associded to database host.}';

    /**
     * @var string
     */
    protected $description = 'Creates a new database host on the system via the CLI.';


    /**
     * Handle the command execution process.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(HostCreationService $creationService)
    {
        $this->creationService = $creationService;

        $data['name'] = $this->option('name') ?? $this->ask('Enter a short identifier used to distinguish this location from others');
        $data['host'] = $this->option('host') ?? $this->ask('Enter the IP address or FQDN of database host');
        $data['port'] = $this->option('port') ?? $this->ask('Enter the port that MySQL is running on for this host');
        $data['username'] = $this->option('username') ?? $this->ask('Enter the username of an account that has enough permissions to create new users and databases on the system.');
        $data['password'] = $this->option('host') ?? $this->secret('Enter the password to the account defined');
        $data['node_id'] = $this->option('nodeId') ?? $this->ask('Enter the default node id for this database host when adding a database to a server on the selected node.');
        $database = $this->creationService->handle($data);
        $this->line('Successfully created a new database host with the name ' . $data['name'] . ' and has an id of ' . $database->id . '.');
    }
}
