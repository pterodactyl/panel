<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Pterodactyl\Models\User;
use Pterodactyl\Repositories\UserRepository;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountsController extends Controller
{

    /**
     * Controller Constructor
     */
    public function __construct()
    {

        // All routes in this controller are protected by the authentication middleware.
        $this->middleware('auth');
        $this->middleware('admin');

    }

    public function getIndex(Request $request)
    {
        return view('admin.accounts.index', [
            'users' => User::paginate(20)
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.accounts.new');
    }

    public function getView(Request $request, $id)
    {
        //
    }

    public function postNew(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|min:4|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|regex:((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})',
            'password_confirmation' => 'required'
        ]);

        try {

            $user = new UserRepository;
            $userid = $user->create($request->input('username'), $request->input('email'), $request->input('password'));

            Alert::success('Account has been successfully created.')->flash();
            return redirect()->route('admin.accounts.view', ['id' => $userid]);

        } catch (\Exception $e) {
            Alert::danger('An error occured while attempting to add a new user. Please check the logs or try again.')->flash();
            return redirect()->route('admin.accounts.new');
        }

    }

}
