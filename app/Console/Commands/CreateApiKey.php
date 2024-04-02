<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Pterodactyl\Console\Kernel;

class CreateApiKey extends Command
{
    protected $signature = 'p:panel-api:create-key
        {--description= : The description to assign to the API key.}
        {--file_output= : The location of the file output for the API key.}
        {--allocations=[r, rw]: API permissions for reading and writing allocations.}
        {--database_hosts=[r, rw]: API permissions for reading and writing database hosts.}
        {--eggs=[r, rw]: API permissions for reading and writing eggs.}
        {--locations=[r, rw]: API permissions for reading and writing locations.}
        {--nests=[r, rw]: API permissions for reading and writing nests.}
        {--nodes=[r, rw]: API permissions for reading and writing nodes.}
        {--server_databases=[r, rw]: API permissions for reading and writing server databases.}
        {--servers=[r, rw]: API permissions for reading and writing servers.}
        {--users=[r, rw]: API permissions for reading and writing users.}';

    protected $description = 'Allows the creation of a new API key for the Panel.';

    /**
     * Execute a command to create a new API key for the Panel.
     */
    public function handle(): void
    {
        //
    }
}
