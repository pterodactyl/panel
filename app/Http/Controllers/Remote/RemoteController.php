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

namespace Pterodactyl\Http\Controllers\Remote;

use Carbon\Carbon;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;

class RemoteController extends Controller
{
    /**
     * Handles download request from daemon.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postDownload(Request $request)
    {
        $download = Models\Download::where('token', $request->input('token'))->first();
        if (! $download) {
            return response()->json([
                'error' => 'An invalid request token was recieved with this request.',
            ], 403);
        }

        $download->delete();

        return response()->json([
            'path' => $download->path,
            'server' => $download->server,
        ]);
    }

    /**
     * Handles install toggle request from daemon.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postInstall(Request $request)
    {
        $server = Models\Server::where('uuid', $request->input('server'))->with('node')->first();
        if (! $server) {
            return response()->json([
                'error' => 'No server by that ID was found on the system.',
            ], 422);
        }

        $hmac = $request->input('signed');
        $status = $request->input('installed');

        if (base64_decode($hmac) !== hash_hmac('sha256', $server->uuid, $server->node->daemonSecret, true)) {
            return response()->json([
                'error' => 'Signed HMAC was invalid.',
            ], 403);
        }

        $server->installed = ($status === 'installed') ? 1 : 2;
        $server->save();

        return response()->json([
            'message' => 'Recieved!',
        ], 200);
    }

    /**
     * Handles event from daemon.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     * @deprecated
     */
    public function event(Request $request)
    {
        $server = Models\Server::where('uuid', $request->input('server'))->with('node')->first();
        if (! $server) {
            return response()->json([
                'error' => 'No server by that ID was found on the system.',
            ], 422);
        }

        $hmac = $request->input('signed');
        if (base64_decode($hmac) !== hash_hmac('sha256', $server->uuid, $server->node->daemonSecret, true)) {
            return response()->json([
                'error' => 'Signed HMAC was invalid.',
            ], 403);
        }

        return response('', 201);
    }

    /**
     * Handles configuration data request from daemon.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $token
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function getConfiguration(Request $request, $token)
    {
        // Try to query the token and the node from the database
        try {
            $model = Models\NodeConfigurationToken::with('node')->where('token', $token)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'token_invalid'], 403);
        }

        // Check if token is expired
        if ($model->created_at->lt(Carbon::now())) {
            $model->delete();

            return response()->json(['error' => 'token_expired'], 403);
        }

        // Delete the token, it's one-time use
        $model->delete();

        // Manually as getConfigurationAsJson() returns it in correct format already
        return response($model->node->getConfigurationAsJson())->header('Content-Type', 'text/json');
    }
}
