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
use Pterodactyl\Models\Nest;
use League\Fractal\TransformerAbstract;

class ServiceTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
        'options',
        'servers',
        'packs',
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
     * Return a generic transformed service array.
     *
     * @param Nest $service
     * @return array
     */
    public function transform(Nest $service)
    {
        return $service->toArray();
    }

    /**
     * Return the the service options.
     *
     * @param Nest $service
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeOptions(Nest $service)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('option-list')) {
            return;
        }

        return $this->collection($service->options, new OptionTransformer($this->request), 'option');
    }

    /**
     * Return the servers associated with this service.
     *
     * @param Nest $service
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeServers(Nest $service)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-list')) {
            return;
        }

        return $this->collection($service->servers, new ServerTransformer($this->request), 'server');
    }

    /**
     * Return the packs associated with this service.
     *
     * @param Nest $service
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includePacks(Nest $service)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('pack-list')) {
            return;
        }

        return $this->collection($service->packs, new PackTransformer($this->request), 'pack');
    }
}
