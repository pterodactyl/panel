<?php
/**
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
