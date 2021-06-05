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
        $data['name'] = $this->option('name') ?? $this->ask(trans('command/messages.database-host.ask_name'));
        $data['host'] = $this->option('host') ?? $this->ask(trans('command/messages.database-host.ask_host'));
        $data['port'] = $this->option('port') ?? $this->ask(trans('command/messages.database-host.ask_port'));
        $data['username'] = $this->option('username') ?? $this->ask(trans('command/messages.database-host.ask_username'));
        $data['password'] = $this->option('password') ?? $this->ask(trans('command/messages.database-host.ask_password'));
        $data['node_id'] = $this->option('node_id') ?? $this->ask(trans('command/messages.database-host.ask_node_id'));

        $dbhost = $this->creationService->handle($data);
        $this->line(trans('command/messages.database-host.created', [
            'name' => $dbhost->name,
            'node' => $dbhost->node_id,
        ]));
    }
}
