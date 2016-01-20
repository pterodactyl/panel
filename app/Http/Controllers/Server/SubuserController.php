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
namespace Pterodactyl\Http\Controllers\Server;

use DB;
use Auth;
use Alert;
use Log;

use Pterodactyl\Models;
use Pterodactyl\Repositories\SubuserRepository;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

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

        $server = Models\Server::getByUUID($uuid);
        $this->authorize('edit-subuser', $server);

        $subuser = Models\Subuser::where(DB::raw('md5(id)'), $id)->where('server_id', $server->id)->first();

        try {

            if (!$subuser) {
                throw new DisplayException('Unable to locate a subuser by that ID.');
            } else if ($subuser->user_id === Auth::user()->id) {
                throw new DisplayException('You are not authorized to edit you own account.');
            }

            $repo = new SubuserRepository;
            $repo->update($subuser->id, [
                'permissions' => $request->input('permissions'),
                'server' => $server->id,
                'user' => $subuser->user_id
            ]);

            Alert::success('Subuser permissions have successfully been updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('server.subusers.view', [
                'uuid' => $uuid,
                'id' => $id
            ])->withErrors(json_decode($ex->getMessage()));
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unknown error occured while attempting to update this subuser.')->flash();
        }
        return redirect()->route('server.subusers.view', [
            'uuid' => $uuid,
            'id' => $id
        ]);
    }

    public function getNew(Request $request, $uuid)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('create-subuser', $server);

        return view('server.users.new', [
            'server' => $server,
            'node' => Models\Node::find($server->node)
        ]);
    }

    public function postNew(Request $request, $uuid)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('create-subuser', $server);

        try {
            $repo = new SubuserRepository;
            $id = $repo->create($server->id, $request->except([
                '_token'
            ]));
            Alert::success('Successfully created new subuser.')->flash();
            return redirect()->route('server.subusers.view', [
                'uuid' => $uuid,
                'id' => md5($id)
            ]);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('server.subusers.new', $uuid)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unknown error occured while attempting to add a new subuser.')->flash();
        }
        return redirect()->route('server.subusers.new', $uuid)->withInput();
    }

    public function deleteSubuser(Request $request, $uuid, $id)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('delete-subuser', $server);

        try {
            $subuser = Models\Subuser::select('id')->where(DB::raw('md5(id)'), $id)->where('server_id', $server->id)->first();
            if (!$subuser) {
                throw new DisplayException('No subuser by that ID was found on the system.');
            }

            $repo = new SubuserRepository;
            $repo->delete($subuser->id);
            return response('', 204);
        } catch (DisplayException $ex) {
            response()->json([
                'error' => $ex->getMessage()
            ], 422);
        } catch (\Exception $ex) {
            Log::error($ex);
            response()->json([
                'error' => 'An unknown error occured while attempting to delete this subuser.'
            ], 503);
        }
    }

}
