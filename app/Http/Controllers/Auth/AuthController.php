<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Pterodactyl\Models\User;

use Validator;
use Auth;

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
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
    	$this->validate($request, [
    		$this->loginUsername() => 'required', 'password' => 'required',
    	]);

    	$throttles = $this->isUsingThrottlesLoginsTrait();

    	if ($throttles && $this->hasTooManyLoginAttempts($request)) {
    		return $this->sendLockoutResponse($request);
    	}

    	$credentials = $this->getCredentials($request);

    	if (Auth::attempt($credentials, $request->has('remember'))) {
    		if(User::select('id')->where('email', $request->input('email'))->where('use_totp', 1)->exists()) {
                $validator = Validator::make($request->all(), [
                    'totp_token' => 'required|numeric'
                ]);

                if($validator->fails()) {
                    Auth::logout();
                    return redirect('auth/login')->withErrors($validator)->withInput();
                }

                $google2fa = new Google2FA();

    			if($google2fa->verifyKey(User::where('email', $request->input('email'))->first()->totp_secret, $request->input('totp_token'))) {
    				return $this->handleUserWasAuthenticated($request, $throttles);
    			} else {
    				Auth::logout();
                    $validator->errors()->add('field', trans('validation.welcome'));
                    return redirect('auth/login')->withErrors($validator)->withInput();
    			}
    		} else {
                return $this->handleUserWasAuthenticated($request, $throttles);
    		}
    	}

    	if ($throttles) {
    		$this->incrementLoginAttempts($request);
    	}

    	return redirect($this->loginPath())
    		->withInput($request->only($this->loginUsername(), 'remember'))
    		->withErrors([
    			$this->loginUsername() => $this->getFailedLoginMessage(),
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
    protected $maxLoginAttempts = 5;

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

}
