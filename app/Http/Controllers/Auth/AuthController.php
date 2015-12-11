<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Pterodactyl\Models\User;

use Auth;
use Alert;
use Validator;

use Pterodactyl\Http\Controllers\Controller;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Post-Authentication redirect location.
     *
     * @var string
     */
    protected $redirectPath = '/';

    /**
     * Failed post-authentication redirect location.
     *
     * @var string
     */
    protected $loginPath = '/auth/login';

    /**
     * Lockout time for failed login requests.
     *
     * @var integer
     */
    protected $lockoutTime = 120;

    /**
     * After how many attempts should logins be throttled and locked.
     *
     * @var integer
     */
    protected $maxLoginAttempts = 3;

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'getLogout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password']),
        ]);
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttled = $this->isUsingThrottlesLoginsTrait();
        if ($throttled && $this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        $G2FA = new Google2FA();
        $user = User::select('use_totp', 'totp_secret')->where('email', $request->input($this->loginUsername()))->first();

        // Verify TOTP Token was Valid
        if($user->use_totp === 1) {
            if(!$G2FA->verifyKey($user->totp_secret, $request->input('totp_token'))) {

                if ($throttled) {
                    $this->incrementLoginAttempts($request);
                }

                Alert::danger(trans('auth.totp_failed'))->flash();
                return redirect()->route('auth.login')->withInput($request->only('email', 'remember'));

            }
        }

        // Attempt to Login
        if (Auth::attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ], $request->has('remember'))) {
            return $this->handleUserWasAuthenticated($request, $throttled);
        }

        if ($throttled) {
            $this->incrementLoginAttempts($request);
        }

        return redirect()->route('auth.login')
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => $this->getFailedLoginMessage(),
                ]);
    }

    /**
     * Check if the provided user has TOTP enabled.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkTotp(Request $request)
    {
        return response()->json(User::select('id')->where('email', $request->input('email'))->where('use_totp', 1)->first());
    }

}
