<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\ViewComposers\Server;

use Illuminate\View\View;
use Illuminate\Contracts\Session\Session;

class ServerDataComposer
{
    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * ServerDataComposer constructor.
     *
     * @param \Illuminate\Contracts\Session\Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Attach server data to a view automatically.
     *
     * @param \Illuminate\View\View $view
     */
    public function compose(View $view)
    {
        $data = $this->session->get('server_data');

        $view->with('server', array_get($data, 'model'));
        $view->with('node', object_get($data['model'], 'node'));
        $view->with('daemon_token', array_get($data, 'token'));
    }
}
