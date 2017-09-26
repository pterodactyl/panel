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
use Pterodactyl\Models\ServiceOption;
use League\Fractal\TransformerAbstract;

class OptionTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
        'service',
        'packs',
        'servers',
        'variables',
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
     * Return a generic transformed service option array.
     *
     * @return array
     */
    public function transform(ServiceOption $option)
    {
        return $option->toArray();
    }

    /**
     * Return the parent service for this service option.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeService(ServiceOption $option)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('service-view')) {
            return;
        }

        return $this->item($option->service, new ServiceTransformer($this->request), 'service');
    }

    /**
     * Return the packs associated with this service option.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includePacks(ServiceOption $option)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('pack-list')) {
            return;
        }

        return $this->collection($option->packs, new PackTransformer($this->request), 'pack');
    }

    /**
     * Return the servers associated with this service option.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeServers(ServiceOption $option)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-list')) {
            return;
        }

        return $this->collection($option->servers, new ServerTransformer($this->request), 'server');
    }

    /**
     * Return the variables for this service option.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeVariables(ServiceOption $option)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('option-view')) {
            return;
        }

        return $this->collection($option->variables, new ServiceVariableTransformer($this->request), 'variable');
    }
}
