<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Debugbar;
use Hash;
use Uuid;

use Pterodactyl\Models\User;
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

        //@TODO: re-generate UUID if conflict
        $user = new User;
        $user->uuid = Uuid::generate(4);

        $user->username = $request->input('username');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));

        $user->save();

        Alert::success('Account has been successfully created.')->flash();
        return redirect()->route('admin.accounts.view', ['id' => $user->id]);
    }

}
