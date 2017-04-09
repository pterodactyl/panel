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
use Pterodactyl\Models\Pack;
use League\Fractal\TransformerAbstract;

class PackTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
        'option',
        'servers',
    ];

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
     * @return void
     */
    public function __construct($request = false)
    {
        if (! $request instanceof Request && $request !== false) {
            throw new DisplayException('Request passed to constructor must be of type Request or false.');
        }

        $this->request = $request;
    }

    /**
     * Return a generic transformed pack array.
     *
     * @return array
     */
    public function transform($pack)
    {
        if (! $pack instanceof Pack) {
            return ['id' => null];
        }

        return $pack->toArray();
    }

    /**
     * Return the packs associated with this service.
     *
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includeOption(Pack $pack)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('pack-view')) {
            return;
        }

        return $this->item($pack->option, new OptionTransformer($this->request), 'option');
    }

    /**
     * Return the packs associated with this service.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeServers(Pack $pack)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('pack-view')) {
            return;
        }

        return $this->collection($pack->servers, new ServerTransformer($this->request), 'server');
    }
}
