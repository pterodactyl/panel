<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Traits\Controllers;

use Javascript;
use Illuminate\Http\Request;

trait JavascriptInjection
{
    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * Set the request object to use when injecting JS.
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Injects the exact array passed in, nothing more.
     *
     * @param array $args
     *
     * @return array
     */
    public function plainInject($args = [])
    {
        return Javascript::put($args);
    }
}
