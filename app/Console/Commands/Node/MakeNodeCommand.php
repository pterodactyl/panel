<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Node;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Nodes\CreatesNodes;

#[Description('Creates a new node on the system via the CLI.')]
#[Signature('p:node:make
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
                            {--daemonBase= : Enter the base folder.}')]
class MakeNodeCommand extends Command
{
    /**
     * Handle the command execution process.
     */
    public function handle(CreatesNodes $nodes): int
    {
        $input = [
            'name' => $this->option('name') ?? $this->ask('Enter a short identifier used to distinguish this node from others'),
            'description' => $this->option('description') ?? $this->ask('Enter a description to identify the node'),
            'location_id' => $this->option('locationId') ?? $this->ask('Enter a valid location id'),
            'scheme' => $this->option('scheme') ?? $this->anticipate(
                'Please either enter https for SSL or http for a non-ssl connection',
                ['https', 'http'],
                'https'
            ),
            'fqdn' => $this->option('fqdn') ?? $this->ask('Enter a domain name (e.g node.example.com) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node'),
            'public' => $this->option('public') ?? $this->confirm('Should this node be public? As a note, setting a node to private you will be denying the ability to auto-deploy to this node.', true),
            'behind_proxy' => $this->option('proxy') ?? $this->confirm('Is your FQDN behind a proxy?'),
            'maintenance_mode' => $this->option('maintenance') ?? $this->confirm('Should maintenance mode be enabled?'),
            'memory' => $this->option('maxMemory') ?? $this->ask('Enter the maximum amount of memory'),
            'memory_overallocate' => $this->option('overallocateMemory') ?? $this->ask('Enter the amount of memory to over allocate by, -1 will disable checking and 0 will prevent creating new servers'),
            'disk' => $this->option('maxDisk') ?? $this->ask('Enter the maximum amount of disk space'),
            'disk_overallocate' => $this->option('overallocateDisk') ?? $this->ask('Enter the amount of memory to over allocate by, -1 will disable checking and 0 will prevent creating new server'),
            'upload_size' => $this->option('uploadSize') ?? $this->ask('Enter the maximum filesize upload', '100'),
            'daemon_listen' => $this->option('daemonListeningPort') ?? $this->ask('Enter the wings listening port', '8080'),
            'daemon_sftp' => $this->option('daemonSFTPPort') ?? $this->ask('Enter the wings SFTP listening port', '2022'),
            'daemon_base' => $this->option('daemonBase') ?? $this->ask('Enter the base folder', '/var/lib/pterodactyl/volumes'),
        ];

        try {
            $node = $nodes->create($input);
        } catch (ValidationException $validationException) {
            foreach ($validationException->validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        $this->line('Successfully created a new node on the location '.$node->location_id.' with the name '.$node->name.' and has an id of '.$node->id.'.');

        return 0;
    }
}
