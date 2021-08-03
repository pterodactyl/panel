<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

abstract class AbstractLoginController extends Controller
{
    use AuthenticatesUsers;

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
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * LoginController constructor.
     */
    public function __construct(AuthManager $auth, Repository $config)
    {
        $this->lockoutTime = $config->get('auth.lockout.time');
        $this->maxLoginAttempts = $config->get('auth.lockout.attempts');

        $this->auth = $auth;
        $this->config = $config;
    }

    /**
     * Get the failed login response instance.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    protected function sendFailedLoginResponse(Request $request, Authenticatable $user = null, string $message = null)
    {
        $this->incrementLoginAttempts($request);
        $this->fireFailedLoginEvent($user, [
            $this->getField($request->input('user')) => $request->input('user'),
        ]);

        if ($request->route()->named('auth.login-checkpoint')) {
            throw new DisplayException($message ?? trans('auth.two_factor.checkpoint_failed'));
        }

        throw new DisplayException(trans('auth.failed'));
    }

    /**
     * Send the response after the user was authenticated.
     */
    protected function sendLoginResponse(User $user, Request $request): JsonResponse
    {
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        $this->auth->guard()->login($user, true);

        return JsonResponse::create([
            'data' => [
                'complete' => true,
                'intended' => $this->redirectPath(),
                'user' => $user->toVueObject(),
            ],
        ]);
    }

    /**
     * Determine if the user is logging in using an email or username,.
     *
     * @param string $input
     */
    protected function getField(string $input = null): string
    {
        return ($input && str_contains($input, '@')) ? 'email' : 'username';
    }

    /**
     * Fire a failed login event.
     */
    protected function fireFailedLoginEvent(Authenticatable $user = null, array $credentials = [])
    {
        event(new Failed('auth', $user, $credentials));
    }
}
