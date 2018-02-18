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
     * @param \Illuminate\Http\Request|bool $request
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
        if ($this->request && ! $this->request->apiKeyHasPermission('option-view')) {
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
        if ($this->request && ! $this->request->apiKeyHasPermission('server-list')) {
            return;
        }

        return $this->collection($pack->servers, new ServerTransformer($this->request), 'server');
    }
}
