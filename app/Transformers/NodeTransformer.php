<?php

namespace Pterodactyl\Transformers;

use Pterodactyl\Models\Node;
use League\Fractal\TransformerAbstract;

class NodeTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(Node $node)
    {
        return $node;
    }

}
