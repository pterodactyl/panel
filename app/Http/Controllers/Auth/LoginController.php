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
use Cache;
use Crypt;
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
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $this->incrementLoginAttempts($request);

        $errors = [$this->username() => trans('auth.failed')];

        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }

        return redirect()->route('auth.login')
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Response\RedirectResponse
     */
    public function login(Request $request)
    {
        // Check wether the user identifier is an email address or a username
        $checkField = str_contains($request->input('user'), '@') ? 'email' : 'username';

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        // Determine if the user even exists.
        $user = User::where($checkField, $request->input($this->username()))->first();
        if (! $user) {
            return $this->sendFailedLoginResponse($request);
        }

        // If user uses 2FA, redirect to that page.
        if ($user->use_totp) {
            $token = str_random(64);
            Cache::put($token, [
                'user_id' => $user->id,
                'credentials' => Crypt::encrypt(serialize([
                    $checkField => $request->input($this->username()),
                    'password' => $request->input('password'),
                ])),
            ], 5);

            return redirect()->route('auth.totp')->with('authentication_token', $token);
        }

        $attempt = Auth::attempt([
            $checkField => $request->input($this->username()),
            'password' => $request->input('password'),
            'use_totp' => 0,
        ], $request->has('remember'));

        if ($attempt) {
            return $this->sendLoginResponse($request);
        }

        // Login failed, send response.
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Handle a TOTP implementation page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function totp(Request $request)
    {
        $token = $request->session()->get('authentication_token');

        if (is_null($token) || Auth::user()) {
            return redirect()->route('auth.login');
        }

        return view('auth.totp', [
            'verify_key' => $token,
            'remember' => $request->has('remember'),
        ]);
    }

    /**
     * Handle a TOTP input.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function totpCheckpoint(Request $request)
    {
        $G2FA = new Google2FA();

        if (is_null($request->input('verify_token'))) {
            return $this->sendFailedLoginResponse($request);
        }

        $cache = Cache::pull($request->input('verify_token'));
        $user = User::where('id', $cache['user_id'])->first();

        if (! $user || ! $cache) {
            $this->sendFailedLoginResponse($request);
        }

        if (is_null($request->input('2fa_token'))) {
            return $this->sendFailedLoginResponse($request);
        }

        try {
            $credentials = unserialize(Crypt::decrypt($cache['credentials']));
        } catch (\Illuminate\Contracts\Encryption\DecryptException $ex) {
            return $this->sendFailedLoginResponse($request);
        }

        if (! $G2FA->verifyKey($user->totp_secret, $request->input('2fa_token'), 2)) {
            event(new \Illuminate\Auth\Events\Failed($user, $credentials));

            return $this->sendFailedLoginResponse($request);
        }

        $attempt = Auth::attempt($credentials, $request->has('remember'));

        if ($attempt) {
            return $this->sendLoginResponse($request);
        }

        // Login failed, send response.
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'user';
    }
}
