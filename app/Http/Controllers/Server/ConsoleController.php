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

namespace Pterodactyl\Http\Controllers\Server;

use Illuminate\Contracts\Session\Session;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Controllers\ServerToJavascript;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class ConsoleController extends Controller
{
    use ServerToJavascript;

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

        return view('server.index', ['server' => $server, 'node' => $server->node]);
    }

    /**
     * Render a stand-alone console in the browser.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function console()
    {
        $server = $this->session->get('server_data.model');

        $this->injectJavascript(['config' => [
            'console_count' => $this->config->get('pterodactyl.console.count'),
            'console_freq' => $this->config->get('pterodactyl.console.frequency'),
        ]]);

        return view('server.console', ['server' => $server, 'node' => $server->node]);
    }
}
