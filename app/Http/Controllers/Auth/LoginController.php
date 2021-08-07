<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use LaravelWebauthn\Facades\Webauthn;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class LoginController extends AbstractLoginController
{
    private const SESSION_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

    private const METHOD_TOTP = 'totp';
    private const METHOD_WEBAUTHN = 'webauthn';

    private CacheRepository $cache;
    private UserRepositoryInterface $repository;
    private ViewFactory $view;

    /**
     * LoginController constructor.
     */
    public function __construct(
        AuthManager $auth,
        Repository $config,
        CacheRepository $cache,
        UserRepositoryInterface $repository,
        ViewFactory $view
    ) {
        parent::__construct($auth, $config);

        $this->cache = $cache;
        $this->repository = $repository;
        $this->view = $view;
    }

    /**
     * Handle all incoming requests for the authentication routes and render the
     * base authentication view component.  React will take over at this point and
     * turn the login area into a SPA.
     */
    public function index(): View
    {
        return $this->view->make('templates/auth.core');
    }

    /**
     * Handle a login request to the application.
     *
     * @return \Illuminate\Http\JsonResponse|void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $username = $request->input('user');
        $useColumn = $this->getField($username);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);

            return;
        }

        try {
            /** @var \Pterodactyl\Models\User $user */
            $user = $this->repository->findFirstWhere([[$useColumn, '=', $username]]);
        } catch (RecordNotFoundException $exception) {
            $this->sendFailedLoginResponse($request);

            return;
        }

        // Ensure that the account is using a valid username and password before trying to
        // continue. Previously this was handled in the 2FA checkpoint, however that has
        // a flaw in which you can discover if an account exists simply by seeing if you
        // can proceed to the next step in the login process.
        if (!password_verify($request->input('password'), $user->password)) {
            $this->sendFailedLoginResponse($request, $user);

            return;
        }

        $webauthnKeys = $user->webauthnKeys()->get();

        if (count($webauthnKeys) > 0) {
            $token = Str::random(64);
            $this->cache->put($token, $user->id, CarbonImmutable::now()->addMinutes(5));

            $publicKey = Webauthn::getAuthenticateData($user);
            $request->session()->put(self::SESSION_PUBLICKEY_REQUEST, $publicKey);
            $request->session()->save();

            $methods = [self::METHOD_WEBAUTHN];
            if ($user->use_totp) {
                $methods[] = self::METHOD_TOTP;
            }

            return new JsonResponse([
                'complete' => false,
                'methods' => $methods,
                'confirmation_token' => $token,
                'webauthn' => [
                    'public_key' => $publicKey,
                ],
            ]);
        } elseif ($user->use_totp) {
            $token = Str::random(64);
            $this->cache->put($token, $user->id, CarbonImmutable::now()->addMinutes(5));

            return new JsonResponse([
                'complete' => false,
                'methods' => [self::METHOD_TOTP],
                'confirmation_token' => $token,
            ]);
        }

        return $this->sendLoginResponse($user, $request);
    }
}
