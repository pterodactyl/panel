<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Cake\Chronos\Chronos;
use Lcobucci\JWT\Builder;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Auth\Events\Failed;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Pterodactyl\Traits\Helpers\ProvidesJWTServices;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

abstract class AbstractLoginController extends Controller
{
    use AuthenticatesUsers, ProvidesJWTServices;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    /**
     * @var \Lcobucci\JWT\Builder
     */
    protected $builder;

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
     * @param \Lcobucci\JWT\Builder                                     $builder
     * @param \Illuminate\Contracts\Cache\Repository                    $cache
     * @param \Illuminate\Contracts\Encryption\Encrypter                $encrypter
     * @param \PragmaRX\Google2FA\Google2FA                             $google2FA
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        AuthManager $auth,
        Builder $builder,
        CacheRepository $cache,
        Encrypter $encrypter,
        Google2FA $google2FA,
        UserRepositoryInterface $repository
    ) {
        $this->auth = $auth;
        $this->builder = $builder;
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

        if ($request->route()->named('auth.login-checkpoint')) {
            throw new DisplayException(trans('auth.two_factor.checkpoint_failed'));
        }

        throw new DisplayException(trans('auth.failed'));
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param \Pterodactyl\Models\User $user
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendLoginResponse(User $user, Request $request): JsonResponse
    {
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        $token = $this->builder->setIssuer(config('app.url'))
            ->setAudience(config('app.url'))
            ->setId(str_random(12), true)
            ->setIssuedAt(Chronos::now()->getTimestamp())
            ->setNotBefore(Chronos::now()->getTimestamp())
            ->setExpiration(Chronos::now()->addSeconds(config('session.lifetime'))->getTimestamp())
            ->set('user', $user->only([
                'id', 'uuid', 'username', 'email', 'name_first', 'name_last', 'language', 'root_admin',
            ]))
            ->sign($this->getJWTSigner(), $this->getJWTSigningKey())
            ->getToken();

        $this->auth->guard()->login($user, true);

        return response()->json([
            'complete' => true,
            'intended' => $this->redirectPath(),
            'token' => $token->__toString(),
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
