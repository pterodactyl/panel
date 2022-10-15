<?php

namespace Pterodactyl\Console\Commands\Node;

use Illuminate\Console\Command;
use Pterodactyl\Services\Nodes\NodeCreationService;

class MakeNodeCommand extends Command
{
    protected $signature = 'p:node:make
                            {--name= : A name to identify the node.}
                            {--description= : A description to identify the node.}
                            {--locationId= : A valid locationId.}
                            {--fqdn= : The domain name (e.g node.example.com) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node.}
                            {--public= : Should the node be public or private? (public=1 / private=0).}
                            {--scheme= : Which scheme should be used? (Enable SSL=https / Disable SSL=http).}
                            {--proxy= : Is the daemon behind a proxy? (Yes=1 / No=0).}
                            {--maintenance= : Should maintenance mode be enabled? (Enable Maintenance mode=1 / Disable Maintenance mode=0).}
                            {--maxMemory= : Set the max memory amount.}
                            {--overallocateMemory= : Enter the amount of ram to overallocate (% or -1 to overallocate the maximum).}
                            {--maxDisk= : Set the max disk amount.}
                            {--overallocateDisk= : Enter the amount of disk to overallocate (% or -1 to overallocate the maximum).}
                            {--uploadSize= : Enter the maximum upload filesize.}
                            {--daemonListeningPort= : Enter the wings listening port.}
                            {--daemonSFTPPort= : Enter the wings SFTP listening port.}
                            {--daemonBase= : Enter the base folder.}';

    protected $description = 'Creates a new node on the system via the CLI.';

    /**
     * MakeNodeCommand constructor.
     */
    public function __construct(private NodeCreationService $creationService)
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
        $data['name'] = $this->option('name') ?? $this->ask('Enter a short identifier used to distinguish this node from others');
        $data['description'] = $this->option('description') ?? $this->ask('Enter a description to identify the node');
        $data['location_id'] = $this->option('locationId') ?? $this->ask('Enter a valid location id');
        $data['scheme'] = $this->option('scheme') ?? $this->anticipate(
            'Please either enter https for SSL or http for a non-ssl connection',
            ['https', 'http'],
            'https'
        );
        $data['fqdn'] = $this->option('fqdn') ?? $this->ask('Enter a domain name (e.g node.example.com) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node');
        $data['public'] = $this->option('public') ?? $this->confirm('Should this node be public? As a note, setting a node to private you will be denying the ability to auto-deploy to this node.', true);
        $data['behind_proxy'] = $this->option('proxy') ?? $this->confirm('Is your FQDN behind a proxy?');
        $data['maintenance_mode'] = $this->option('maintenance') ?? $this->confirm('Should maintenance mode be enabled?');
        $data['memory'] = $this->option('maxMemory') ?? $this->ask('Enter the maximum amount of memory');
        $data['memory_overallocate'] = $this->option('overallocateMemory') ?? $this->ask('Enter the amount of memory to over allocate by, -1 will disable checking and 0 will prevent creating new servers');
        $data['disk'] = $this->option('maxDisk') ?? $this->ask('Enter the maximum amount of disk space');
        $data['disk_overallocate'] = $this->option('overallocateDisk') ?? $this->ask('Enter the amount of memory to over allocate by, -1 will disable checking and 0 will prevent creating new server');
        $data['upload_size'] = $this->option('uploadSize') ?? $this->ask('Enter the maximum filesize upload', '100');
        $data['daemonListen'] = $this->option('daemonListeningPort') ?? $this->ask('Enter the wings listening port', '8080');
        $data['daemonSFTP'] = $this->option('daemonSFTPPort') ?? $this->ask('Enter the wings SFTP listening port', '2022');
        $data['daemonBase'] = $this->option('daemonBase') ?? $this->ask('Enter the base folder', '/var/lib/pterodactyl/volumes');

        $node = $this->creationService->handle($data);
        $this->line('Successfully created a new node on the location ' . $data['location_id'] . ' with the name ' . $data['name'] . ' and has an id of ' . $node->id . '.');
    }
}
