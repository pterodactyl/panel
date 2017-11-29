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
use League\Fractal\TransformerAbstract;

class OverviewTransformer extends TransformerAbstract
{
    /**
     * Return a generic transformed server array.
     *
     * @return array
     */
    public function transform(Server $server)
    {
        return [
            'id' => $server->uuidShort,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'node' => $server->node->name,
            'ip' => $server->allocation->alias,
            'port' => $server->allocation->port,
            'service' => $server->service->name,
            'option' => $server->option->name,
        ];
    }
}
