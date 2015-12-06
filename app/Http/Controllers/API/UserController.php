<?php

namespace Pterodactyl\Http\Controllers\API;

use Gate;
use Log;
use Debugbar;
use Pterodactyl\Models\API;
use Pterodactyl\Models\User;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('api');
    }

    public function getAllUsers(Request $request)
    {

        // Policies don't work if the user isn't logged in for whatever reason in Laravel...
        if(!API::checkPermission($request->header('X-Authorization'), 'get-users')) {
            return API::noPermissionError();
        }

        return response()->json([
            'users' => User::all()
        ]);
    }

    /**
     * Returns JSON response about a user given their ID.
     * If fields are provided only those fields are returned.
     *
     * Does not return protected fields (i.e. password & totp_secret)
     *
     * @param  Request $request
     * @param  int     $id
     * @param  string  $fields
     * @return Response
     */
    public function getUser(Request $request, $id, $fields = null)
    {

        // Policies don't work if the user isn't logged in for whatever reason in Laravel...
        if(!API::checkPermission($request->header('X-Authorization'), 'get-users')) {
            return API::noPermissionError();
        }

        if (is_null($fields)) {
            return response()->json(User::find($id));
        }

        $query = User::where('id', $id);
        $explode = explode(',', $fields);

        foreach($explode as &$exploded) {
            if(!empty($exploded)) {
                $query->addSelect($exploded);
            }
        }

        try {
            return response()->json($query->get());
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\QueryException) {
                return response()->json([
                    'error' => 'One of the fields provided in your argument list is invalid.'
                ], 500);
            }
            throw $e;
        }

    }

}
