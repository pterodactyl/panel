<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
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
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\UserRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class UserController extends Controller
{
    /**
     * Display user index page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $users = User::withCount('servers', 'subuserOf');

        if (! is_null($request->input('query'))) {
            $users->search($request->input('query'));
        }

        return view('admin.users.index', [
            'users' => $users->paginate(25),
        ]);
    }

    /**
     * Display new user page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        return view('admin.users.new');
    }

    /**
     * Display user view page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function view(Request $request, $id)
    {
        return view('admin.users.view', [
            'user' => User::with('servers.node')->findOrFail($id),
        ]);
    }

    /**
     * Delete a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request, $id)
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

    /**
     * Create a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $user = new UserRepository;
            $userid = $user->create($request->only([
                'email', 'password', 'name_first',
                'name_last', 'username', 'root_admin',
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

    /**
     * Update a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Rename variables because autofill cannot be disabled
        // in any logical manner, and editing users is impossible.
        $fixedData = array_filter(
            collect($request->all())->mapWithKeys(function ($item, $key) {
                return [str_replace('input_', '', $key) => $item];
            })->only([
                'email', 'password', 'name_first',
                'name_last', 'username', 'root_admin',
            ])->toArray()
        );

        try {
            $repo = new UserRepository;
            $user = $repo->update($id, $fixedData);
            Alert::success('User account was successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.users.view', $id)->withErrors(json_decode($ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to update this user.')->flash();
        }

        return redirect()->route('admin.users.view', $id);
    }

    /**
     * Get a JSON response of users on the system.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Pterodactyl\Models\User
     */
    public function json(Request $request)
    {
        return User::select('id', 'email', 'username', 'name_first', 'name_last')
            ->search($request->input('q'))
            ->get()->transform(function ($item) {
                $item->md5 = md5(strtolower($item->email));

                return $item;
            });
    }
}
