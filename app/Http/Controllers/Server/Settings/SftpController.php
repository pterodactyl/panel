<?php

namespace Pterodactyl\Http\Controllers\Server\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Controllers\JavascriptInjection;

class SftpController extends Controller
{
    use JavascriptInjection;

    /**
     * Render the server SFTP settings page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $this->setRequest($request)->injectJavascript();

        return view('server.settings.sftp');
    }
}
