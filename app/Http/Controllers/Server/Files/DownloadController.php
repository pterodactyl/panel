<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
