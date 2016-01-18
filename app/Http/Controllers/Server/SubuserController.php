<?php

namespace Pterodactyl\Http\Controllers\Server;

use DB;
use Alert;
use Pterodactyl\Models;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;

class SubuserController extends Controller
{

    /**
     * Controller Constructor
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getIndex(Request $request, $uuid)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('list-subusers', $server);

        return view('server.users.index', [
            'server' => $server,
            'node' => Models\Node::find($server->node),
            'subusers' => Models\Subuser::select('subusers.*', 'users.email as a_userEmail')
                ->join('users', 'users.id', '=', 'subusers.user_id')
                ->where('server_id', $server->id)
                ->get()
        ]);

    }

    public function getView(Request $request, $uuid, $id)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('view-subuser', $server);

        $subuser = Models\Subuser::select('subusers.*', 'users.email as a_userEmail')
            ->join('users', 'users.id', '=', 'subusers.user_id')
            ->where(DB::raw('md5(subusers.id)'), $id)->where('subusers.server_id', $server->id)
            ->first();

        if (!$subuser) {
            abort(404);
        }

        $permissions = [];
        $modelPermissions = Models\Permission::select('permission')
            ->where('user_id', $subuser->user_id)->where('server_id', $server->id)
            ->get();

        foreach($modelPermissions as &$perm) {
            $permissions[$perm->permission] = true;
        }

        return view('server.users.view', [
            'server' => $server,
            'node' => Models\Node::find($server->node),
            'subuser' => $subuser,
            'permissions' => $permissions,
        ]);
    }

    public function postView(Request $request, $uuid, $id)
    {
        //
    }

}
