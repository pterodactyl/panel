<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Node;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Pterodactyl\Contracts\Nodes\CreatesNodes;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\NodeRules;

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
            'daemonListen' => $this->option('daemonListeningPort') ?? $this->ask('Enter the wings listening port', '8080'),
            'daemonSFTP' => $this->option('daemonSFTPPort') ?? $this->ask('Enter the wings SFTP listening port', '2022'),
            'daemonBase' => $this->option('daemonBase') ?? $this->ask('Enter the base folder', '/var/lib/pterodactyl/volumes'),
        ];
        JsonValueGuard::assertScalarPayload($input);

        $data = [
            'name' => $this->stringInput($input['name']),
            'description' => $this->stringInput($input['description']),
            'location_id' => $this->integerInput($input['location_id']),
            'scheme' => $this->stringInput($input['scheme']),
            'fqdn' => $this->stringInput($input['fqdn']),
            'public' => $this->booleanInput($input['public']),
            'behind_proxy' => $this->booleanInput($input['behind_proxy']),
            'maintenance_mode' => $this->booleanInput($input['maintenance_mode']),
            'memory' => $this->integerInput($input['memory']),
            'memory_overallocate' => $this->integerInput($input['memory_overallocate']),
            'disk' => $this->integerInput($input['disk']),
            'disk_overallocate' => $this->integerInput($input['disk_overallocate']),
            'upload_size' => $this->integerInput($input['upload_size']),
            'daemonListen' => $this->integerInput($input['daemonListen']),
            'daemonSFTP' => $this->integerInput($input['daemonSFTP']),
            'daemonBase' => $this->stringInput($input['daemonBase']),
        ];

        $validator = Validator::make($data, NodeRules::rules());
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        $node = $nodes->create($data);
        $this->line('Successfully created a new node on the location '.$data['location_id'].' with the name '.$data['name'].' and has an id of '.$node->id.'.');

        return 0;
    }

    /** @param ApiScalar $value */
    private function stringInput(bool|float|int|string|null $value): string
    {
        throw_if(! is_string($value) && ! is_numeric($value), InvalidArgumentException::class, 'Expected a string console input.');

        // SAFETY: the guards above reject arrays and objects before console input is converted.
        return (string) $value;
    }

    /** @param ApiScalar $value */
    private function integerInput(bool|float|int|string|null $value): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        throw_if($integer === false, InvalidArgumentException::class, 'Expected an integer console input.');

        return $integer;
    }

    /** @param ApiScalar $value */
    private function booleanInput(bool|float|int|string|null $value): bool
    {
        $boolean = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        throw_if($boolean === null, InvalidArgumentException::class, 'Expected a boolean console input.');

        return $boolean;
    }
}
