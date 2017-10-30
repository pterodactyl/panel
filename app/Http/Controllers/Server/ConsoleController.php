<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Server;

use Illuminate\Contracts\Session\Session;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class ConsoleController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * ConsoleController constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Illuminate\Contracts\Session\Session   $session
     */
    public function __construct(
        ConfigRepository $config,
        Session $session
    ) {
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * Render server index page with the console and power options.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $server = $this->session->get('server_data.model');

        $this->injectJavascript([
            'meta' => [
                'saveFile' => route('server.files.save', $server->uuidShort),
                'csrfToken' => csrf_token(),
            ],
            'config' => [
                'console_count' => $this->config->get('pterodactyl.console.count'),
                'console_freq' => $this->config->get('pterodactyl.console.frequency'),
            ],
        ]);

        return view('server.index');
    }

    /**
     * Render a stand-alone console in the browser.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function console()
    {
        $this->injectJavascript(['config' => [
            'console_count' => $this->config->get('pterodactyl.console.count'),
            'console_freq' => $this->config->get('pterodactyl.console.frequency'),
        ]]);

        return view('server.console');
    }
}
