<?php

namespace Pterodactyl\Http\ViewComposers\Server;

use Illuminate\View\View;
use Illuminate\Http\Request;

class ServerDataComposer
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * ServerDataComposer constructor.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Attach server data to a view automatically.
     *
     * @param \Illuminate\View\View $view
     */
    public function compose(View $view)
    {
        $server = $this->request->get('server');

        $view->with('server', $server);
        $view->with('node', object_get($server, 'node'));
        $view->with('daemon_token', $this->request->get('server_token'));
    }
}
