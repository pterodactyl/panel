<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Mail;
use Log;
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
        //
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
        return view('admin.accounts.view', [
            'user' => User::findOrFail($id),
            'servers' => Server::select('servers.*', 'nodes.name as nodeName', 'locations.long as location')
                            ->join('nodes', 'servers.node', '=', 'nodes.id')
                            ->join('locations', 'nodes.location', '=', 'locations.id')
                            ->where('active', 1)
                            ->get(),
        ]);
    }

    public function deleteView(Request $request, $id)
    {
        try {
            User::findOrFail($id)->delete();
            return response(null, 204);
        } catch(\Exception $ex) {
            Log::error($ex);
            return response()->json([
                'error' => 'An error occured while attempting to delete this user.'
            ], 500);
        }
    }

    public function postNew(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|regex:((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})'
        ]);

        try {
            $user = new UserRepository;
            $userid = $user->create($request->input('username'), $request->input('email'), $request->input('password'));

            if (!$userid) {
                throw new \Exception('Unable to create user, response was not an integer.');
            }

            Alert::success('Account has been successfully created.')->flash();
            return redirect()->route('admin.accounts.view', ['id' => $userid]);
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An error occured while attempting to add a new user. ' . $e->getMessage())->flash();
            return redirect()->route('admin.accounts.new');
        }

    }

    public function postUpdate(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users,email,'.$request->input('user'),
            'root_admin' => 'required',
            'password' => 'required_with:password_confirmation|confirmed',
            'password_confirmation' => 'required_with:password'
        ]);

        try {

            $users = new UserRepository;
            $user = [
                'email' => $request->input('email'),
                'root_admin' => $request->input('root_admin')
            ];

            if(!empty($request->input('password'))) {
                $user['password'] = $request->input('password');
            }

            if(!$users->update($request->input('user'), $user)) {
                throw new \Exception('Unable to update user, response was not valid.');
            }

            if($request->input('email_user')) {
                Mail::send('emails.new_password', ['user' => User::findOrFail($request->input('user')), 'password' => $request->input('password')], function($message) use ($request) {
                    $message->to($request->input('email'))->subject('Pterodactyl - Admin Reset Password');
                });
            }

            Alert::success('User account was successfully updated.')->flash();
            return redirect()->route('admin.accounts.view', ['id' => $request->input('user')]);

        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An error occured while attempting to update this user. ' . $e->getMessage())->flash();
            return redirect()->route('admin.accounts.view', ['id' => $request->input('user')]);
        }
    }

}
