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

namespace Pterodactyl\Http\Controllers\Base;

use Alert;
use Google2FA;
use Illuminate\Http\Request;
use Pterodactyl\Models\Session;
use Pterodactyl\Http\Controllers\Controller;

class SecurityController extends Controller
{
    /**
     * Returns Security Management Page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('base.security', [
            'sessions' => Session::where('user_id', $request->user()->id)->get(),
        ]);
    }

    /**
     * Generates TOTP Secret and returns popup data for user to verify
     * that they can generate a valid response.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateTotp(Request $request)
    {
        $user = $request->user();

        $user->totp_secret = Google2FA::generateSecretKey();
        $user->save();

        return response()->json([
            'qrImage' => Google2FA::getQRCodeGoogleUrl(
                'Pterodactyl',
                $user->email,
                $user->totp_secret
            ),
            'secret' => $user->totp_secret,
        ]);
    }

    /**
     * Verifies that 2FA token recieved is valid and will work on the account.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function setTotp(Request $request)
    {
        if (! $request->has('token')) {
            return response()->json([
                'error' => 'Request is missing token parameter.',
            ], 500);
        }

        $user = $request->user();
        if ($user->toggleTotp($request->input('token'))) {
            return response('true');
        }

        return response('false');
    }

    /**
     * Disables TOTP on an account.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disableTotp(Request $request)
    {
        if (! $request->has('token')) {
            Alert::danger('Missing required `token` field in request.')->flash();

            return redirect()->route('account.security');
        }

        $user = $request->user();
        if ($user->toggleTotp($request->input('token'))) {
            return redirect()->route('account.security');
        }

        Alert::danger('The TOTP token provided was invalid.')->flash();

        return redirect()->route('account.security');
    }

    /**
     * Revokes a user session.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revoke(Request $request, $id)
    {
        Session::where('user_id', $request->user()->id)->findOrFail($id)->delete();

        return redirect()->route('account.security');
    }
}
