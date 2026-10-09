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
     * @param  ServerCreationInput  $data
     *
     * @throws Throwable
     * @throws DisplayException
     * @throws ValidationException
     * @throws NoViableNodeException
     * @throws NoViableAllocationException
     */
    public function create(array $data, ?DeploymentObject $deployment = null): Server;
}
