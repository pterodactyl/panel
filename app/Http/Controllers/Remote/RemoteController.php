<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
