<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
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
use Validator;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;

class BaseController extends Controller
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
        return view('admin.index');
    }

    public function getSettings(Request $request)
    {
        return view('admin.settings');
    }

    public function postSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company' => 'required|between:1,256',
            'default_language' => 'required|alpha_dash|min:2|max:5',
            'email_from' => 'required|email',
            'email_sender_name' => 'required|between:1,256',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.settings')->withErrors($validator->errors())->withInput();
        }

        Settings::set('company', $request->input('company'));
        Settings::set('default_language', $request->input('default_language'));
        Settings::set('email_from', $request->input('email_from'));
        Settings::set('email_sender_name', $request->input('email_sender_name'));

        Alert::success('Settings have been successfully updated.')->flash();

        return redirect()->route('admin.settings');
    }
}
