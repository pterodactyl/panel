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

use Storage;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;

class PackController extends Controller
{
    /**
     * Controller Constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * Pulls an install pack archive from the system.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function pull(Request $request, $uuid)
    {
        $pack = Models\Pack::where('uuid', $uuid)->first();

        if (! $pack) {
            return response()->json(['error' => 'No such pack.'], 404);
        }

        if (! Storage::exists('packs/' . $pack->uuid . '/archive.tar.gz')) {
            return response()->json(['error' => 'There is no archive available for this pack.'], 503);
        }

        return response()->download(storage_path('app/packs/' . $pack->uuid . '/archive.tar.gz'));
    }

    /**
     * Returns the hash information for a pack.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function hash(Request $request, $uuid)
    {
        $pack = Models\Pack::where('uuid', $uuid)->first();

        if (! $pack) {
            return response()->json(['error' => 'No such pack.'], 404);
        }

        if (! Storage::exists('packs/' . $pack->uuid . '/archive.tar.gz')) {
            return response()->json(['error' => 'There is no archive available for this pack.'], 503);
        }

        return response()->json([
            'archive.tar.gz' => sha1_file(storage_path('app/packs/' . $pack->uuid . '/archive.tar.gz')),
        ]);
    }

    /**
     * Pulls an update pack archive from the system.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function pullUpdate(Request $request)
    {
    }
}
