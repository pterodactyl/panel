<?php

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
