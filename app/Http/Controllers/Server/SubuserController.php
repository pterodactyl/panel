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

namespace Pterodactyl\Http\Controllers\Server;

use Log;
use Auth;
use Alert;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\SubuserRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class SubuserController extends Controller
{
    /**
     * Displays the subuser overview index.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @return \Illuminate\View\View
     */
    public function index(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid)->load('subusers.user');
        $this->authorize('list-subusers', $server);

        $server->js();

        return view('server.users.index', [
            'server' => $server,
            'node' => $server->node,
            'subusers' => $server->subusers,
        ]);
    }

    /**
     * Displays the a single subuser overview.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function view(Request $request, $uuid, $id)
    {
        $server = Models\Server::byUuid($uuid)->load('node');
        $this->authorize('view-subuser', $server);

        $subuser = Models\Subuser::with('permissions', 'user')
            ->where('server_id', $server->id)->findOrFail($id);

        $server->js();

        return view('server.users.view', [
            'server' => $server,
            'node' => $server->node,
            'subuser' => $subuser,
            'permlist' => Models\Permission::listPermissions(),
            'permissions' => $subuser->permissions->mapWithKeys(function ($item, $key) {
                return [$item->permission => true];
            }),
        ]);
    }

    /**
     * Handles editing a subuser.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $uuid, $id)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('edit-subuser', $server);

        $subuser = Models\Subuser::where('server_id', $server->id)->findOrFail($id);

        try {
            if ($subuser->user_id === Auth::user()->id) {
                throw new DisplayException('You are not authorized to edit you own account.');
            }

            $repo = new SubuserRepository;
            $repo->update($subuser->id, [
                'permissions' => $request->input('permissions'),
                'server' => $server->id,
                'user' => $subuser->user_id,
            ]);

            Alert::success('Subuser permissions have successfully been updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('server.subusers.view', [
                'uuid' => $uuid,
                'id' => $id,
            ])->withErrors(json_decode($ex->getMessage()));
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unknown error occured while attempting to update this subuser.')->flash();
        }

        return redirect()->route('server.subusers.view', [
            'uuid' => $uuid,
            'id' => $id,
        ]);
    }

    /**
     * Display new subuser creation page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @return \Illuminate\View\View
     */
    public function create(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('create-subuser', $server);
        $server->js();

        return view('server.users.new', [
            'server' => $server,
            'permissions' => Models\Permission::listPermissions(),
            'node' => $server->node,
        ]);
    }

    /**
     * Handles creating a new subuser.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('create-subuser', $server);

        try {
            $repo = new SubuserRepository;
            $subuser = $repo->create($server->id, $request->only([
                'permissions', 'email',
            ]));
            Alert::success('Successfully created new subuser.')->flash();

            return redirect()->route('server.subusers.view', [
                'uuid' => $uuid,
                'id' => $subuser->id,
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

    /**
     * Handles deleting a subuser.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @param  int                       $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function delete(Request $request, $uuid, $id)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('delete-subuser', $server);

        try {
            $subuser = Models\Subuser::where('server_id', $server->id)->findOrFail($id);

            $repo = new SubuserRepository;
            $repo->delete($subuser->id);

            return response('', 204);
        } catch (DisplayException $ex) {
            response()->json([
                'error' => $ex->getMessage(),
            ], 422);
        } catch (\Exception $ex) {
            Log::error($ex);
            response()->json([
                'error' => 'An unknown error occured while attempting to delete this subuser.',
            ], 503);
        }
    }
}
