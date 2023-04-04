<?php

namespace Pterodactyl\Console\Commands\Database;

use Illuminate\Console\Command;
use Pterodactyl\Services\Databases\Hosts\HostCreationService;

class MakeDatabaseCommand extends Command
{
    protected $signature = 'p:database:make
                            {--name= : A short identifier used to distinguish this location from others. Must be between 1 and 60 characters, for example, us.nyc.lvl3.}
                            {--host= : The IP address or FQDN that should be used when attempting to connect to this MySQL host from the panel to add new databases.}
                            {--port= : The port that MySQL is running on for this host.}
                            {--username= : The username of an account that has enough permissions to create new users and databases on the system.}
                            {--password= : The password to the account defined.}
                            {--nodeId= : This setting does nothing other than default to this database host when adding a database to a server on the selected node.}';

    protected $description = 'Adds a new database host in pterodactyl';

    /**
     * MakeDatabaseCommand constructor.
     */
    public function __construct(private HostCreationService $creationService)
    {
        parent::__construct();
    }

    /**
     * Handle the command execution process.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle()
    {
        $data['name'] = $this->option('name') ?? $this->ask('A short identifier used to distinguish this location from others. Must be between 1 and 60 characters, for example, us.nyc.lvl3.');
        $data['host'] = $this->option('host') ?? $this->ask('The IP address or FQDN that should be used when attempting to connect to this MySQL host from the panel to add new databases.');
        $data['port'] = $this->option('port') ?? $this->ask('The port that MySQL is running on for this host.');
        $data['username'] = $this->option('username') ?? $this->ask('The username of an account that has enough permissions to create new users and databases on the system.');
        $data['password'] = $this->option('password') ?? $this->secret('The password to the account defined.');
        $data['node_id'] = $this->option('nodeId');

        $node = $this->creationService->handle($data);
        $this->line('The new database has been successfully added to your panel');
    }
}
