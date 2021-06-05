<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Allocation;

use Illuminate\Console\Command;
use Pterodactyl\Services\Allocations\AllocationCreationService;

class MakeAllocationCommand extends Command
{
    /**
     * @var \Pterodactyl\Services\Allocations\AllocationCreationService
     */
    protected $creationService;

    /**
     * @var string
     */
    protected $signature = 'p:allocation:make
                            {--nodeid= : A valid Node ID.}
                            {--ip= : The IP Address of the machine.}
                            {--port= : The Port to create.}
                            {--alias= : The Alias to assign to the Port.}';

    /**
     * @var string
     */
    protected $description = 'Creates a new allocation on the system via the CLI.';


    /**
     * Handle the command execution process.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(AllocationCreationService $creationService)
    {
        $this->creationService = $creationService;
        
        $data['node_id'] = $this->option('nodeid') ?? $this->ask('Enter a valid Node ID');
        $data['ip'] = $this->option('ip') ?? $this->ask('Enter the IP Address of the machine');
        $data['port'] = $this->option('port') ?? $this->ask('Enter the Port to be created');
        $aliaschoice = $this->confirm('Do you wish to create an alias for this allocation?');
        if($aliaschoice) {
            $data['alias'] = $this->option('alias') ?? $this->ask('Enter an alias if you wish to assign one to the Port');
        };

        $allocation = $this->creationService->handle($data);
        $this->line('Successfully created the allocation ' . $data['ip'] . $data['port'] . ' on node ' . $data['node_id'] . '.');
    }
}
