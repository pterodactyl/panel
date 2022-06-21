<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\ShareConsoleRequest;

class ConsoleShareController extends ClientApiController
{
    /**
     * ConsoleShareController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets the latest console logs and sends them to MCPaste.
     */
    public function index(ShareConsoleRequest $request, Server $server): array
    {
        $data = '
        --------------------------------------------------------------------
        Jexactyl Share System
        --------------------------------------------------------------------
        User: ' . $request->user()->email . '
        Server: ' . $server->uuid . '
        Server Image: ' . $server->image . '
        Uploaded on: ' . \Carbon\Carbon::now()->toDateTimeString() . '
        --------------------------------------------------------------------
        ' . $request->input('data');

        return Http::asForm()->post('https://api.mcpaste.com/create', [
            'url' => $request->getHttpHost(),
            'data' => $data,
        ])->json();
    }
}
