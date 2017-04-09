<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
     * @param  \Illuminate\Http\Request|bool  $request
     * @param  bool                           $filter
     * @return void
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
     * @return array
     */
    public function transform(Allocation $allocation)
    {
        return $this->transformWithFilter($allocation);
    }

    /**
     * Determine which transformer filter to apply.
     *
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
     * @return array
     */
    protected function transformForServer(Allocation $allocation)
    {
        return collect($allocation)->only('id', 'ip', 'ip_alias', 'port')->toArray();
    }
}
