<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Illuminate\Validation\ValidationException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Models\Server;
use Throwable;

interface CreatesServers
{
    /**
     * Wings is asked to install the server, which is deleted again when Wings cannot be reached.
     * The input is validated here; extension field values go under `extensions` as
     * ValidatedExtensionValues, and the environment is checked against the egg's variable rules.
     * Without a deployment the server takes the allocation_id given; with one, the deployment
     * picks its node and default allocation.
     *
     * @param  array<string, mixed>  $input  name, owner_id, egg_id, image, startup, memory, swap, disk, io, cpu and, without a deployment, allocation_id, and optionally external_id, description, environment, skip_scripts, oom_disabled, threads, database_limit, allocation_limit, backup_limit, allocation_additional and start_on_completion
     *
     * @throws Throwable
     * @throws DisplayException
     * @throws ValidationException
     * @throws NoViableNodeException
     * @throws NoViableAllocationException
     */
    public function create(array $input, ?DeploymentObject $deployment = null): Server;
}
