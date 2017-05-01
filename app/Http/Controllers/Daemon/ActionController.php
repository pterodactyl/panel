<?php
/**
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

namespace Pterodactyl\Http\Controllers\Daemon;

use Cache;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Controller;

class ActionController extends Controller
{
    /**
     * Handles download request from daemon.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateDownload(Request $request)
    {
        $download = Cache::tags(['Server:Downloads'])->pull($request->input('token'));

        if (is_null($download)) {
            return response()->json([
                'error' => 'An invalid request token was recieved with this request.',
            ], 403);
        }

        return response()->json([
            'path' => $download['path'],
            'server' => $download['server'],
        ]);
    }

    /**
     * Handles install toggle request from daemon.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markInstall(Request $request)
    {
        $server = Server::where('uuid', $request->input('server'))->with('node')->first();
        if (! $server) {
            return response()->json([
                'error' => 'No server by that ID was found on the system.',
            ], 422);
        }

        $hmac = $request->input('signed');
        $status = $request->input('installed');

        if (! hash_equals(base64_decode($hmac), hash_hmac('sha256', $server->uuid, $server->node->daemonSecret, true))) {
            return response()->json([
                'error' => 'Signed HMAC was invalid.',
            ], 403);
        }

        $server->installed = ($status === 'installed') ? 1 : 2;
        $server->save();

        return response()->json([]);
    }

    /**
     * Handles configuration data request from daemon.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $token
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function configuration(Request $request, $token)
    {
        $nodeId = Cache::tags(['Node:Configuration'])->pull($token);
        if (is_null($nodeId)) {
            return response()->json(['error' => 'token_invalid'], 403);
        }

        $node = Node::findOrFail($nodeId);

        // Manually as getConfigurationAsJson() returns it in correct format already
        return response($node->getConfigurationAsJson())->header('Content-Type', 'text/json');
    }
}
