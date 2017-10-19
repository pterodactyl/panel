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
     * Injects server javascript into the page to be used by other services.
     *
     * @param array                         $args
     * @param bool                          $overwrite
     * @param \Illuminate\Http\Request|null $request
     * @return array
     */
    public function injectJavascript($args = [], $overwrite = false, Request $request = null)
    {
        $request = $request ?? app()->make(Request::class);
        $server = $request->attributes->get('server');
        $token = $request->attributes->get('server_token');

        $response = array_merge([
            'server' => [
                'uuid' => $server->uuid,
                'uuidShort' => $server->uuidShort,
                'daemonSecret' => $token,
                'username' => $server->username,
            ],
            'node' => [
                'fqdn' => $server->node->fqdn,
                'scheme' => $server->node->scheme,
                'daemonListen' => $server->node->daemonListen,
            ],
        ], $args);

        return Javascript::put($overwrite ? $args : $response);
    }
}
