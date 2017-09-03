<?php
/*
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
