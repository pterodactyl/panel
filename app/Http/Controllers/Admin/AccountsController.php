<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>
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
use Settings;
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
        try {
            $user = new UserRepository;
            $userid = $user->create($request->input('email'), $request->input('password'));
            Alert::success('Account has been successfully created.')->flash();
            return redirect()->route('admin.accounts.view', ['id' => $userid]);
        } catch (\Pterodactyl\Exceptions\DisplayValidationException $ex) {
            return redirect()->route('admin.accounts.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to add a new user. ' . $ex->getMessage())->flash();
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
                Mail::queue('emails.new_password', ['user' => User::findOrFail($request->input('user')), 'password' => $request->input('password')], function($message) use ($request) {
                    $message->to($request->input('email'))->subject(Settings::get('company') . ' - Admin Reset Password');
                    $message->from(Settings::get('email_from', env('MAIL_FROM')), Settings::get('email_sender_name', env('MAIL_FROM_NAME', 'Pterodactyl Panel')));
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
