<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>.
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

namespace Pterodactyl\Http\Controllers\Admin;

use Log;
use Alert;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\UserRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class UserController extends Controller
{
    /**
     * Controller Constructor.
     */
    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        $query = User::select('users.*');
        if ($request->input('filter') && ! is_null($request->input('filter'))) {
            preg_match_all('/[^\s"\']+|"([^"]*)"|\'([^\']*)\'/', urldecode($request->input('filter')), $matches);
            foreach ($matches[0] as $match) {
                $match = str_replace('"', '', $match);
                if (strpos($match, ':')) {
                    list($field, $term) = explode(':', $match);
                    $query->orWhere($field, 'LIKE', '%' . $term . '%');
                } else {
                    $query->where('email', 'LIKE', '%' . $match . '%');
                    $query->orWhere([
                        ['uuid', 'LIKE', '%' . $match . '%'],
                        ['root_admin', 'LIKE', '%' . $match . '%'],
                    ]);
                }
            }
        }

        try {
            $users = $query->paginate(20);
        } catch (\Exception $ex) {
            Alert::warning('There was an error with the search parameters provided.');
            $users = User::all()->paginate(20);
        }

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.users.new');
    }

    public function getView(Request $request, $id)
    {
        return view('admin.users.view', [
            'user' => User::findOrFail($id),
            'servers' => Server::select('servers.*', 'nodes.name as nodeName', 'locations.long as location')
                ->join('nodes', 'servers.node', '=', 'nodes.id')
                ->join('locations', 'nodes.location', '=', 'locations.id')
                ->where('owner', $id)
                ->get(),
        ]);
    }

    public function deleteUser(Request $request, $id)
    {
        try {
            $repo = new UserRepository;
            $repo->delete($id);
            Alert::success('Successfully deleted user from system.')->flash();

            return redirect()->route('admin.users');
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An exception was encountered while attempting to delete this user.')->flash();
        }

        return redirect()->route('admin.users.view', $id);
    }

    public function postNew(Request $request)
    {
        try {
            $user = new UserRepository;
            $userid = $user->create($request->only([
                'email',
                'password',
                'name_first',
                'name_last',
                'username'
            ]));
            Alert::success('Account has been successfully created.')->flash();

            return redirect()->route('admin.users.view', $userid);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.users.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to add a new user.')->flash();

            return redirect()->route('admin.users.new');
        }
    }

    public function updateUser(Request $request, $user)
    {
        try {
            $repo = new UserRepository;
            $repo->update($user, $request->only([
                'email',
                'password',
                'name_first',
                'name_last',
                'username',
                'root_admin',
            ]));
            Alert::success('User account was successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.users.view', $user)->withErrors(json_decode($ex->getMessage()));
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An error occured while attempting to update this user.')->flash();
        }

        return redirect()->route('admin.users.view', $user);
    }

    public function getJson(Request $request)
    {
        foreach (User::select('email')->get() as $user) {
            $resp[] = $user->email;
        }

        return $resp;
    }
}
