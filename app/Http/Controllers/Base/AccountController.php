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

namespace Pterodactyl\Http\Controllers\Base;

use Log;
use Alert;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Repositories\UserRepository;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\DisplayValidationException;

class AccountController extends Controller
{
    /**
     * Display base account information page.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        return view('base.account');
    }

    /**
     * Update details for a users account.
     * @param  \Illuminate\Http\Request $request
     * @return void
     */
    public function update(Request $request)
    {
        $data = [];

        // Request to update account Password
        if ($request->input('do_action') === 'password') {
            $this->validate($request, [
                'current_password' => 'required',
                'new_password' => 'required|confirmed|' . User::PASSWORD_RULES,
                'new_password_confirmation' => 'required',
            ]);

            $data['password'] = $request->input('new_password');

        // Request to update account Email
        } else if ($request->input('do_action') === 'email') {
            $data['email'] = $request->input('new_email');

        // Request to update account Identity
        } else if ($request->input('do_action') === 'identity') {
            $data = $request->only(['name_first', 'name_last', 'username']);

        // Unknown, hit em with a 404
        } else {
            return abort(404);
        }

        if (
            in_array($request->input('do_action'), ['email', 'password'])
            && ! password_verify($request->input('password'), $request->user()->password)
        ) {
            Alert::danger(trans('base.account.invalid_pass'))->flash();
            return redirect()->route('account');
        }

        try {
            $repo = new UserRepository;
            $repo->update($request->user()->id, $data);
            Alert::success('Your account details were successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('account')->withErrors(json_decode($ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger(trans('base.account.exception'))->flash();
        }

        return redirect()->route('account');
    }
}
