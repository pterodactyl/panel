<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Mail;
use Pterodactyl\Models\User;
use Pterodactyl\Repositories\UserRepository;
use Pterodactyl\Models\Server;

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
        return view('admin.accounts.view', ['user' => User::findOrFail($id), 'servers' => Server::where('owner', $id)->first()]);
    }

    public function getDelete(Request $request, $id)
    {
        $user = new UserRepository;
        $user->delete($id);

        Alert::success('An account has been successfully deleted.')->flash();
        return redirect()->route('admin.accounts');
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

    public function postUpdate(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users,email,'.$request->input('user'),
            'root_admin' => 'required',
            'password' => 'required_with:password_confirmation',
            'password_confirmation' => 'required_with:password'
        ]);

        try {

            $users = new UserRepository;
            $user = [];

            $user['email'] = $request->input('email');
            $user['root_admin'] = $request->input('root_admin');

            if(!empty($request->input('password'))) {
                $user['password'] = $request->input('password');
            }

            $users->update($request->input('user'), $user);

        } catch (\Exception $e) {
            Alert::danger('An error occured while attempting to update a user. Please check the logs or try again.')->flash();
            return redirect()->route('admin.accounts.view', ['id' => $request->input('user')]);
        }

        if($request->input('email_user')) {
            Mail::send('emails.new_password', ['user' => User::findOrFail($request->input('user')), 'password' => $request->input('password')], function($message) use ($request) {
                $message->to($request->input('email'))->subject('Pterodactyl - Admin Reset Password');
            });
        }

        Alert::success('A user was successfully updated.')->flash();
        return redirect()->route('admin.accounts.view', ['id' => $request->input('user')]);

    }

}
