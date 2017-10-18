<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Transformers\User;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use League\Fractal\TransformerAbstract;

class AllocationTransformer extends TransformerAbstract
{
    /**
     * Server eloquent model.
     *
     * @return \Pterodactyl\Models\Server
     */
    protected $server;

    /**
     * Setup allocation transformer with access to server data.
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Return a generic transformed allocation array.
     *
     * @return array
     */
    public function transform(Allocation $allocation)
    {
        return [
            'id' => $allocation->id,
            'ip' => $allocation->alias,
            'port' => $allocation->port,
            'default' => ($allocation->id === $this->server->allocation_id),
        ];
    }
}
