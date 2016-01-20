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

use Pterodactyl\Models\Download;
use Pterodactyl\Exceptions\DisplayException;

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
        $download = Download::where('token', $request->input('token', '00'))->first();
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

}
