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
namespace Pterodactyl\Http\Controllers\Base;

use Alert;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;

use Illuminate\Http\Request;

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
     * Update an account email.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function email(Request $request)
    {

        $this->validate($request, [
            'new_email' => 'required|email',
            'password' => 'required'
        ]);

        $user = $request->user();

        if (!password_verify($request->input('password'), $user->password)) {
            Alert::danger('The password provided was not valid for this account.')->flash();
            return redirect()->route('account');
        }

        $user->email = $request->input('new_email');
        $user->save();

        Alert::success('Your email address has successfully been updated.')->flash();
        return redirect()->route('account');

    }

    /**
     * Update an account password.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function password(Request $request)
    {

        $this->validate($request, [
            'current_password' => 'required',
            'new_password' => 'required|confirmed|different:current_password|' . Models\User::PASSWORD_RULES,
            'new_password_confirmation' => 'required'
        ]);

        $user = $request->user();

        if (!password_verify($request->input('current_password'), $user->password)) {
            Alert::danger('The password provided was not valid for this account.')->flash();
            return redirect()->route('account');
        }

        try {
            $user->setPassword($request->input('new_password'));
            Alert::success('Your password has successfully been updated.')->flash();
        } catch (DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        }

        return redirect()->route('account');

    }
}
