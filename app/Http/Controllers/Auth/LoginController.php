<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Traits\Helpers\OAuth2Providers;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class LoginController extends Controller
{
    use AuthenticatesUsers, OAuth2Providers;

    const USER_INPUT_FIELD = 'user';

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * @var \PragmaRX\Google2FA\Google2FA
     */
    private $google2FA;

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
    protected $lockoutTime;

    /**
     * After how many attempts should logins be throttled and locked.
     *
     * @var int
     */
    protected $maxLoginAttempts;

    /**
     * LoginController constructor.
     *
     * @param \Illuminate\Auth\AuthManager                              $auth
     * @param \Illuminate\Contracts\Cache\Repository                    $cache
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \Illuminate\Contracts\Encryption\Encrypter                $encrypter
     * @param \PragmaRX\Google2FA\Google2FA                             $google2FA
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        AuthManager $auth,
        CacheRepository $cache,
        ConfigRepository $config,
        Encrypter $encrypter,
        Google2FA $google2FA,
        UserRepositoryInterface $repository
    ) {
        $this->auth = $auth;
        $this->cache = $cache;
        $this->config = $config;
        $this->encrypter = $encrypter;
        $this->google2FA = $google2FA;
        $this->repository = $repository;

        $this->lockoutTime = $this->config->get('auth.lockout.time');
        $this->maxLoginAttempts = $this->config->get('auth.lockout.attempts');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        $providers = $this->getEnabledProviderSettings();

        return view('auth.login', compact('providers'));
    }

    /**
     * Handle a login request to the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $username = $request->input(self::USER_INPUT_FIELD);
        $useColumn = $this->getField($username);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        try {
            $user = $this->repository->findFirstWhere([[$useColumn, '=', $username]]);
        } catch (RecordNotFoundException $exception) {
            return $this->sendFailedLoginResponse($request);
        }

        if ($this->config->get('oauth2.required') == 2 or ($user->root_admin == true and $this->config->get('oauth2.required') == 1)) {
            return $this->sendFailedLoginResponse($request);
        }

        $validCredentials = password_verify($request->input('password'), $user->password);
        if ($user->use_totp) {
            $token = str_random(64);
            $this->cache->put($token, ['user_id' => $user->id, 'valid_credentials' => $validCredentials], 5);

            return redirect()->route('auth.totp')->with('authentication_token', $token);
        }

        if ($validCredentials) {
            $this->auth->guard()->login($user, true);

            return $this->sendLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request, $user);
    }

    /**
     * Handle a TOTP implementation page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function totp(Request $request)
    {
        $token = $request->session()->get('authentication_token');
        if (is_null($token) || $this->auth->guard()->user()) {
            return redirect()->route('auth.login');
        }

        return view('auth.totp', ['verify_key' => $token]);
    }

    /**
     * Handle a login where the user is required to provide a TOTP authentication
     * token. In order to add additional layers of security, users are not
     * informed of an incorrect password until this stage, forcing them to
     * provide a token on each login attempt.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function loginUsingTotp(Request $request)
    {
        if (is_null($request->input('verify_token'))) {
            return $this->sendFailedLoginResponse($request);
        }

        try {
            $cache = $this->cache->pull($request->input('verify_token'), []);
            $user = $this->repository->find(array_get($cache, 'user_id', 0));
        } catch (RecordNotFoundException $exception) {
            return $this->sendFailedLoginResponse($request);
        }

        if (is_null($request->input('2fa_token')) || ! array_get($cache, 'valid_credentials')) {
            return $this->sendFailedLoginResponse($request, $user);
        }

        if (! $this->google2FA->verifyKey(
            $this->encrypter->decrypt($user->totp_secret),
            $request->input('2fa_token'),
            $this->config->get('pterodactyl.auth.2fa.window')
        )) {
            return $this->sendFailedLoginResponse($request, $user);
        }

        $this->auth->guard()->login($user, true);

        return $this->sendLoginResponse($request);
    }

    /**
     * Get the failed login response instance.
     *
     * @param \Illuminate\Http\Request                        $request
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendFailedLoginResponse(Request $request, Authenticatable $user = null): RedirectResponse
    {
        $this->incrementLoginAttempts($request);
        $this->fireFailedLoginEvent($user, [
            $this->getField($request->input(self::USER_INPUT_FIELD)) => $request->input(self::USER_INPUT_FIELD),
        ]);

        $errors = [self::USER_INPUT_FIELD => trans('auth.failed')];

        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }

        return redirect()->route('auth.login')
            ->withInput($request->only(self::USER_INPUT_FIELD))
            ->withErrors($errors);
    }

    /**
     * Determine if the user is logging in using an email or username,.
     *
     * @param string $input
     * @return string
     */
    private function getField(string $input = null): string
    {
        return str_contains($input, '@') ? 'email' : 'username';
    }

    /**
     * Fire a failed login event.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param array                                           $credentials
     */
    private function fireFailedLoginEvent(Authenticatable $user = null, array $credentials = [])
    {
        event(new Failed(config('auth.defaults.guard'), $user, $credentials));
    }
}
