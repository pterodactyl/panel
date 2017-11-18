<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Transformers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\Allocation;
use League\Fractal\TransformerAbstract;

class AllocationTransformer extends TransformerAbstract
{
    /**
     * The filter to be applied to this transformer.
     *
     * @var bool|string
     */
    protected $filter;

    /**
     * The Illuminate Request object if provided.
     *
     * @var \Illuminate\Http\Request|bool
     */
    protected $request;

    /**
     * Setup request object for transformer.
     *
     * @param \Illuminate\Http\Request|bool $request
     * @param bool                          $filter
     */
    public function __construct($request = false, $filter = false)
    {
        if (! $request instanceof Request && $request !== false) {
            throw new DisplayException('Request passed to constructor must be of type Request or false.');
        }

        $this->request = $request;
        $this->filter = $filter;
    }

    /**
     * Return a generic transformed allocation array.
     *
     * @param Allocation $allocation
     * @return array
     */
    public function transform(Allocation $allocation)
    {
        return $this->transformWithFilter($allocation);
    }

    /**
     * Determine which transformer filter to apply.
     *
     * @param Allocation $allocation
     * @return array
     */
    protected function transformWithFilter(Allocation $allocation)
    {
        if ($this->filter === 'server') {
            return $this->transformForServer($allocation);
        }

        return $allocation->toArray();
    }

    /**
     * Transform the allocation to only return information not duplicated
     * in the server response (discard node_id and server_id).
     *
     * @param Allocation $allocation
     * @return array
     */
    protected function transformForServer(Allocation $allocation)
    {
        return collect($allocation)->only('id', 'ip', 'ip_alias', 'port')->toArray();
    }
}
