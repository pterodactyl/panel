<?php

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Transformers\Api\ApiTransformer;

class AllocationTransformer extends ApiTransformer
{
    /**
     * Return a generic transformed allocation array.
     *
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return array
     */
    public function transform(Allocation $allocation)
    {
        return $this->transformWithFilter($allocation);
    }

    /**
     * Determine which transformer filter to apply.
     *
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return array
     */
    protected function transformWithFilter(Allocation $allocation)
    {
        return $allocation->toArray();
    }
}
