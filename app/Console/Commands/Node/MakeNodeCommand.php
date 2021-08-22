<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Node;

use Illuminate\Console\Command;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class MakeNodeCommand extends Command
{
    /**
     * @var \Pterodactyl\Services\Nodes\NodeCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var string
     */
    protected $signature = 'p:node:make
                            {--name= : Node name.}
                            {--description= : A longer description of this node.}
                            {--location_short= : The short code of the location.}
                            {--fqdn= : The FQDN of the node.}
                            {--scheme= : Communicate Over SSL.}
                            {--behind_proxy= : Behind Proxy.}
                            {--daemonBase= : Daemon Server File Directory.}
                            {--memory= : Total Memory.}
                            {--memory_overallocate= : Memory Over-Allocation.}
                            {--disk= : Total Disk Space.}
                            {--disk_overallocate= : Disk Over-Allocation.}
                            {--daemonListen= : Daemon Port.}
                            {--daemonSFTP= : Daemon SFTP Port.}';

    /**
     * @var string
     */
    protected $description = 'Creates a new node on the system via the CLI.';

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $locations;

    /**
     * Create a new command instance.
     */
    public function __construct(NodeCreationService $creationService, LocationRepositoryInterface $locationRepository)
    {
        parent::__construct();

        $this->creationService = $creationService;
        $this->locationRepository = $locationRepository;
    }

    /**
     * Handle the command execution process.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle()
    {
        $this->locations = $this->locations ?? $this->locationRepository->all();
        $name = $this->option('name') ?? $this->ask(trans('command/messages.node.ask_name'));
        $description = $this->option('description') ?? $this->ask(trans('command/messages.node.ask_description'));
        $location_short = $this->option('location_short') ?? $this->anticipate(trans('command/messages.node.ask_location_id'), $this->locations->pluck('short')->toArray());
        $location = $this->locations->where('short', $location_short)->first();

        if (is_null($location)) {
            $this->error(trans('command/messages.location.no_location_found'));
            if ($this->input->isInteractive()) {
                $this->handle();
            }

            return;
        }

        $location_id = $location->id;
        $fqdn = $this->option('fqdn') ?? $this->ask(trans('command/messages.node.ask_fqdn'));
        $scheme = $this->option('scheme') ?? $this->choice(trans('command/messages.node.ask_scheme'), [
            'https' => 'HTTPS', 'http' => 'HTTP'
        ], 'http');
        $behind_proxy = $this->option('behind_proxy') ?? $this->choice(trans('command/messages.node.ask_behind_proxy'), [
            0 => 'No', 1 => 'Yes'
        ], 0);
        $daemonBase = $this->option('daemonBase') ?? $this->ask(trans('command/messages.node.ask_daemonBase'), '/var/lib/pterodactyl/volumes');
        $memory = $this->option('memory') ?? $this->ask(trans('command/messages.node.ask_memory'));
        $memory_overallocate = $this->option('memory_overallocate') ?? $this->ask(trans('command/messages.node.ask_memory_overallocate'), 0);
        $disk = $this->option('disk') ?? $this->ask(trans('command/messages.node.ask_disk'));
        $disk_overallocate = $this->option('disk_overallocate') ?? $this->ask(trans('command/messages.node.ask_disk_overallocate'), 0);
        $daemonListen = $this->option('daemonListen') ?? $this->ask(trans('command/messages.node.ask_daemonListen'), 8080);
        $daemonSFTP = $this->option('daemonSFTP') ?? $this->ask(trans('command/messages.node.ask_daemonSFTP'), 2022);

        $node = $this->creationService->handle(compact('name', 'description', 'location_id', 'fqdn', 'scheme', 'behind_proxy', 'daemonBase', 'memory', 'memory', 'memory_overallocate', 'disk',
            'disk_overallocate', 'daemonListen', 'daemonSFTP'));
        $this->line(trans('command/messages.node.created', [
            'name' => $node->name,
            'id' => $node->id,
        ]));
    }
}
