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
use Pterodactyl\Models\ServerVariable;
use League\Fractal\TransformerAbstract;

class ServerVariableTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['parent'];

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
     * Return a generic transformed server variable array.
     *
     * @return array
     */
    public function transform(ServerVariable $variable)
    {
        return $variable->toArray();
    }

    /**
     * Return the parent service variable data.
     *
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includeParent(ServerVariable $variable)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('option-view')) {
            return;
        }

        return $this->item($variable->variable, new ServiceVariableTransformer($this->request), 'variable');
    }
}
