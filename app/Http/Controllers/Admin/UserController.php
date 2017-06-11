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

use Alert;
use Illuminate\Http\Request;
use Pterodactyl\Contracts\Repositories\UserInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Requests\Admin\UserFormRequest;
use Pterodactyl\Models\User;
use Pterodactyl\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\UserRepository
     */
    protected $repository;

    /**
     * UserController constructor.
     *
     * @param \Pterodactyl\Contracts\Repositories\UserInterface $repository
     */
    public function __construct(UserInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display user index page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $users = $this->repository->withCount('servers', 'subuserOf');

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
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.users.new');
    }

    /**
     * Display user view page.
     *
     * @param  \Pterodactyl\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function view(User $user)
    {
        return view('admin.users.view', [
            'user' => $user,
        ]);
    }

    /**
     * Delete a user from the system.
     *
     * @param  \Pterodactyl\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(User $user)
    {
        try {
            $this->repository->delete($user->id);

            return redirect()->route('admin.users');
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Create a user.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\UserFormRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UserFormRequest $request)
    {
        $user = $this->repository->create($request->normalize());
        Alert::success('Account has been successfully created.')->flash();

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Update a user on the system.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\UserFormRequest  $request
     * @param  \Pterodactyl\Models\User                          $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UserFormRequest $request, User $user)
    {
        $this->repository->update($user->id, $request->normalize());

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Get a JSON response of users on the system.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Pterodactyl\Models\User
     */
    public function json(Request $request)
    {
        return $this->repository->search($request->input('q'))->all([
            'id', 'email', 'username', 'name_first', 'name_last',
        ])->transform(function ($item) {
            $item->md5 = md5(strtolower($item->email));

            return $item;
        });
    }
}
