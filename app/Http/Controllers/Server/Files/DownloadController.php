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

namespace Pterodactyl\Http\Controllers\Server\Files;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Session\Session;
use Pterodactyl\Http\Controllers\Controller;

class DownloadController extends Controller
{
    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * DownloadController constructor.
     *
     * @param \Illuminate\Cache\Repository          $cache
     * @param \Illuminate\Contracts\Session\Session $session
     */
    public function __construct(Repository $cache, Session $session)
    {
        $this->cache = $cache;
        $this->session = $session;
    }

    /**
     * Setup a unique download link for a user to download a file from.
     *
     * @param string $uuid
     * @param string $file
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index($uuid, $file)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('download-files', $server);

        $token = str_random(40);
        $this->cache->tags(['Server:Downloads'])->put($token, ['server' => $server->uuid, 'path' => $file], 5);

        return redirect(sprintf(
            '%s://%s:%s/server/file/download/%s', $server->node->scheme, $server->node->fqdn, $server->node->daemonListen, $token
        ));
    }
}
