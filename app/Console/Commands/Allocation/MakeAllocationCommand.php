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
     * Create a new command instance.
     */
    public function __construct(AllocationCreationService $creationService)
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
        $node_id = $this->option('nodeid') ?? $this->ask(trans('command/messages.allocation.ask_nodeid'));
        $ip = $this->option('ip') ?? $this->ask(trans('command/messages.allocation.ask_ip'));
        $port = $this->option('port') ?? $this->ask(trans('command/messages.allocation.ask_port'));
        $ip_alias = $this->option('alias') ?? $this->ask(trans('command/messages.allocation.ask_alias'));

        $allocation = $this->creationService->handle(compact('node_id', 'ip', 'ip_alias', 'port'));
        $this->line(trans('command/messages.allocation.created', [
            'ip' => $allocation->ip,
            'port' => $allocation->port,
            'node' => $allocation->node_id,
        ]));
    }
}
