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

trait JavascriptInjection
{
    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * Injects server javascript into the page to be used by other services.
     *
     * @param array $args
     * @param bool  $overwrite
     * @return mixed
     */
    public function injectJavascript($args = [], $overwrite = false)
    {
        $server = $this->session->get('server_data.model');
        $token = $this->session->get('server_data.token');

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
