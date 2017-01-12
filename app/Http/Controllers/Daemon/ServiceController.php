<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>.
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

use Storage;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;

class ServiceController extends Controller
{
    /**
     * Controller Constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * Returns a listing of all services currently on the system,
     * as well as the associated files and the file hashes for
     * caching purposes.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $response = [];
        foreach (Models\Service::all() as &$service) {
            $response[$service->file] = [
                'main.json' => sha1_file(storage_path('app/services/' . $service->file . '/main.json')),
                'index.js' => sha1_file(storage_path('app/services/' . $service->file . '/index.js')),
            ];
        }

        return response()->json($response);
    }

    /**
     * Returns the contents of the requested file for the given service.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  string                     $service
     * @param  string                     $file
     * @return \Illuminate\Http\Response
     */
    public function pull(Request $request, $service, $file)
    {
        if (! Storage::exists('services/' . $service . '/' . $file)) {
            return response()->json(['error' => 'No such file.'], 404);
        }

        return response()->file(storage_path('app/services/' . $service . '/' . $file));
    }
}
