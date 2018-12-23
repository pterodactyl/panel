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
     * @param \Illuminate\Http\Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Injects server javascript into the page to be used by other services.
     *
     * @param array $args
     * @param bool  $overwrite
     * @return array
     */
    public function injectJavascript($args = [], $overwrite = false)
    {
        $request = $this->request ?? app()->make(Request::class);
        $server = $request->attributes->get('server');
        $token = $request->attributes->get('server_token');

        $response = array_merge_recursive([
            'server' => [
                'uuid' => $server->uuid,
                'uuidShort' => $server->uuidShort,
                'daemonSecret' => $token,
            ],
            'server_token' => $token,
            'node' => [
                'fqdn' => $server->node->fqdn,
                'scheme' => $server->node->scheme,
                'daemonListen' => $server->node->daemonListen,
            ],
        ], $args);

        return Javascript::put($overwrite ? $args : $response);
    }
}
