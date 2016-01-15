<?php

namespace Pterodactyl\Transformers;

use Pterodactyl\Models\Server;
use League\Fractal\TransformerAbstract;

class ServerTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(Server $server)
    {
        return $server;
    }

}
