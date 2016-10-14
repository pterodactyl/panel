<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
namespace Pterodactyl\Http\Controllers\Remote;

use Pterodactyl\Models;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\NotificationService;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RemoteController extends Controller
{

    /**
     * Controller Constructor
     */
    public function __construct()
    {
        // No middleware for this route.
    }

    public function postDownload(Request $request) {
        $download = Models\Download::where('token', $request->input('token', '00'))->first();
        if (!$download) {
            return response()->json([
                'error' => 'An invalid request token was recieved with this request.'
            ], 403);
        }

        $download->delete();
        return response()->json([
            'path' => $download->path,
            'server' => $download->server
        ]);
    }

    public function postInstall(Request $request)
    {
        $server = Models\Server::where('uuid', $request->input('server'))->first();
        if (!$server) {
            return response()->json([
                'error' => 'No server by that ID was found on the system.'
            ], 422);
        }

        $node = Models\Node::findOrFail($server->node);
        $hmac = $request->input('signed');
        $status = $request->input('installed');

        if (base64_decode($hmac) !== hash_hmac('sha256', $server->uuid, $node->daemonSecret, true)) {
            return response()->json([
                'error' => 'Signed HMAC was invalid.'
            ], 403);
        }

        $server->installed = ($status === 'installed') ? 1 : 2;
        $server->save();

        return response()->json([
            'message' => 'Recieved!'
        ], 200);
    }

    public function event(Request $request)
    {
        $server = Models\Server::where('uuid', $request->input('server'))->first();
        if (!$server) {
            return response()->json([
                'error' => 'No server by that ID was found on the system.'
            ], 422);
        }

        $node = Models\Node::findOrFail($server->node);

        $hmac = $request->input('signed');
        if (base64_decode($hmac) !== hash_hmac('sha256', $server->uuid, $node->daemonSecret, true)) {
            return response()->json([
                'error' => 'Signed HMAC was invalid.'
            ], 403);
        }

        // Passes Validation, Setup Notifications
        $notify = new NotificationService($server);
        $notify->pass($request->input('notification'));

        return response('', 201);
    }

}
