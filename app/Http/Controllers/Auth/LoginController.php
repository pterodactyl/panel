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

namespace Pterodactyl\Http\Controllers\Auth;

use Auth;
use Alert;
use Cache;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Lockout time for failed login requests.
     *
     * @var int
     */
    protected $lockoutTime = 120;

    /**
     * After how many attempts should logins be throttled and locked.
     *
     * @var int
     */
    protected $maxLoginAttempts = 3;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($lockedOut = $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        // Is the email & password valid?
        if (! Auth::once([
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ], $request->has('remember'))) {
            if (! $lockedOut) {
                $this->incrementLoginAttempts($request);
            }

            return $this->sendFailedLoginResponse($request);
        }

        // Verify TOTP Token was Valid
        if (Auth::user()->use_totp) {
            $verifyKey = str_random(64);
            Cache::put($verifyKey, Auth::user()->id, 5);

            return redirect()->route('auth.totp')->with('authentication_token', $verifyKey);
        } else {
            Auth::login(Auth::user(), $request->has('remember'));

            return $this->sendLoginResponse($request);
        }
    }

    public function totp(Request $request)
    {
        $verifyKey = $request->session()->get('authentication_token');

        if (is_null($verifyKey) || Auth::user()) {
            return redirect()->route('auth.login');
        }

        return view('auth.totp', [
            'verify_key' => $verifyKey,
            'remember' => $request->has('remember'),
        ]);
    }

    public function totpCheckpoint(Request $request)
    {
        $G2FA = new Google2FA();

        if (is_null($request->input('verify_token'))) {
            $this->incrementLoginAttempts($request);
            Alert::danger(trans('auth.totp_failed'))->flash();

            return redirect()->route('auth.login');
        }

        $user = User::where('id', Cache::pull($request->input('verify_token')))->first();
        if (! $user) {
            $this->incrementLoginAttempts($request);
            Alert::danger(trans('auth.totp_failed'))->flash();

            return redirect()->route('auth.login');
        }

        if (! is_null($request->input('2fa_token')) && $G2FA->verifyKey($user->totp_secret, $request->input('2fa_token'), 1)) {
            Auth::login($user, $request->has('remember'));

            return redirect()->intended($this->redirectPath());
        } else {
            $this->incrementLoginAttempts($request);
            Alert::danger(trans('auth.2fa_failed'))->flash();

            return redirect()->route('auth.login');
        }
    }
}
