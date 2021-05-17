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

class MakeNodeCommand extends Command
{
    /**
     * @var \Pterodactyl\Services\Nodes\NodeCreationService
     */
    protected $creationService;

    /**
     * @var string
     */
    protected $signature = 'p:node:make
                            {--name= : A name to identify the node.}
                            {--description= : A description to identify the node.}
                            {--locationid= : A valid locationid.}
                            {--fqdn= : The domain name (e.g node.example.com) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node.}
                            {--public= : Should the node be public or private? (public=1 / private=0).}
                            {--scheme= : Which scheme should be used? (Enable SSL=https / Disable SSL=http).}
                            {--proxy= : Is the daemon behind the cloudflare proxy? (Yes=1 / No=0).}
                            {--maintenance= : Should maintenance mode be enabled? (Enable Maintenance mode=1 / Disable Maintenance mode=0).}
                            {--maxMemory= : Set the max memory amount.}
                            {--overallocateMemory= : Enter the amount of ram to overallocate (% or -1 to overallocate the maximum).}
                            {--maxDisk= : Set the max disk amount.}
                            {--overallocateDisk= : Enter the amount of disk to overallocate (% or -1 to overallocate the maximum).}
                            {--uploadSize= : Enter the maximum upload filesize.}
                            {--daemonListeningPort= : Enter the wings listening port.}
                            {--daemonSFTPPort= : Enter the wings SFTP listening port.}
                            {--daemonBase= : Enter the base folder.}';

    /**
     * @var string
     */
    protected $description = 'Creates a new node on the system via the CLI.';

    /**
     * Create a new command instance.
     */
    public function __construct(NodeCreationService $creationService)
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
        $name = $this->option('name') ?? $this->ask(trans('command/messages.node.ask_name'));
        $description = $this->option('description') ?? $this->ask(trans('command/messages.node.ask_description'));
        $location_id = $this->option('locationid') ?? $this->ask(trans('command/messages.node.ask_location_id'));
        $fqdn = $this->option('fqdn') ?? $this->ask(trans('command/messages.node.ask_fqdn'));
        if (!filter_var(gethostbyname($fqdn), FILTER_VALIDATE_IP)) {
            $this->error(trans('admin/node.validation.fqdn_not_resolvable'));
            return;
        }
        $public = $this->option('public') ?? $this->ask(trans('command/messages.node.ask_public'));
        $scheme = $this->option('scheme') ?? $this->ask(trans('command/messages.node.ask_scheme'));
        if ($scheme !== 'https' && $scheme !== 'http') {
            $this->error(trans('command/messages.node.no_valid_scheme'));
            return;
        }
        if (filter_var($fqdn, FILTER_VALIDATE_IP) && $scheme === 'https') {
            $this->error(trans('admin/node.validation.fqdn_required_for_ssl'));
            return;
        }
        $behind_proxy = $this->option('proxy') ?? $this->ask(trans('command/messages.node.ask_behind_proxy'));
        $maintenance_mode = $this->option('maintenance') ?? $this->ask(trans('command/messages.node.ask_maintenance_mode'));
        $memory = $this->option('maxMemory') ?? $this->ask(trans('command/messages.node.ask_memory'));
        $memory_overallocate = $this->option('overallocateMemory') ?? $this->ask(trans('command/messages.node.ask_memory_overallocate'));
        $disk = $this->option('maxDisk') ?? $this->ask(trans('command/messages.node.ask_disk'));
        $disk_overallocate = $this->option('overallocateDisk') ?? $this->ask(trans('command/messages.node.ask_disk_overallocate'));
        $upload_size = $this->option('uploadSize') ?? $this->ask(trans('command/messages.node.ask_upload_size'));
        $daemonListen = $this->option('daemonListeningPort') ?? $this->ask(trans('command/messages.node.ask_daemonListen'));
        $daemonSFTP = $this->option('daemonSFTPPort') ?? $this->ask(trans('command/messages.node.ask_daemonSFTP'));
        $daemonBase = $this->option('daemonBase') ?? $this->ask(trans('command/messages.node.ask_daemonBase'));

        $node = $this->creationService->handle(compact('name', 'description', 'location_id', 'fqdn', 'public', 'scheme', 'behind_proxy', 'maintenance_mode', 'memory', 'memory_overallocate', 'disk', 'disk_overallocate', 'upload_size', 'daemonListen', 'daemonSFTP', 'daemonBase'));
        $this->line(trans('command/messages.node.created', [
            'location' => $node->location_id,
            'name' => $node->name,
            'id' => $node->id,
        ]));
    }
}
