<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Auth\Events\Failed;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

abstract class AbstractLoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * @var \PragmaRX\Google2FA\Google2FA
     */
    protected $google2FA;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

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
     * LoginController constructor.
     *
     * @param \Illuminate\Auth\AuthManager                              $auth
     * @param \Illuminate\Contracts\Cache\Repository                    $cache
     * @param \Illuminate\Contracts\Encryption\Encrypter                $encrypter
     * @param \PragmaRX\Google2FA\Google2FA                             $google2FA
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        AuthManager $auth,
        CacheRepository $cache,
        Encrypter $encrypter,
        Google2FA $google2FA,
        UserRepositoryInterface $repository
    ) {
        $this->auth = $auth;
        $this->cache = $cache;
        $this->encrypter = $encrypter;
        $this->google2FA = $google2FA;
        $this->repository = $repository;

        $this->lockoutTime = config('auth.lockout.time');
        $this->maxLoginAttempts = config('auth.lockout.attempts');
    }

    /**
     * Get the failed login response instance.
     *
     * @param \Illuminate\Http\Request                        $request
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    protected function sendFailedLoginResponse(Request $request, Authenticatable $user = null)
    {
        $this->incrementLoginAttempts($request);
        $this->fireFailedLoginEvent($user, [
            $this->getField($request->input('user')) => $request->input('user'),
        ]);

        if ($request->route()->named('auth.checkpoint')) {
            throw new DisplayException(trans('auth.checkpoint_failed'));
        }

        throw new DisplayException(trans('auth.failed'));
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendLoginResponse(Request $request): JsonResponse
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        return $this->authenticated($request, $this->guard()->user())
            ?: response()->json([
                'intended' => $this->redirectPath(),
            ]);
    }

    /**
     * Determine if the user is logging in using an email or username,.
     *
     * @param string $input
     * @return string
     */
    protected function getField(string $input = null): string
    {
        return str_contains($input, '@') ? 'email' : 'username';
    }

    /**
     * Fire a failed login event.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param array                                           $credentials
     */
    protected function fireFailedLoginEvent(Authenticatable $user = null, array $credentials = [])
    {
        event(new Failed($user, $credentials));
    }
}
