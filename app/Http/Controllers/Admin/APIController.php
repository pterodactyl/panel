<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Log;
use Pterodactyl\Models;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class APIController extends Controller
{

    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        $keys = Models\APIKey::all();
        foreach($keys as &$key) {
            $key->permissions = Models\APIPermission::where('key_id', $key->id)->get();
        }

        return view('admin.api.index', [
            'keys' => $keys
        ]);
    }

}
