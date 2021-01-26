<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class LoginController extends AbstractLoginController
{
    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    private $view;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

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

        $this->view = $view;
        $this->cache = $cache;
        $this->repository = $repository;
    }

    /**
     * Handle all incoming requests for the authentication routes and render the
     * base authentication view component. Vuejs will take over at this point and
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
    public function login(Request $request): JsonResponse
    {
        $username = $request->input('user');
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

        // Ensure that the account is using a valid username and password before trying to
        // continue. Previously this was handled in the 2FA checkpoint, however that has
        // a flaw in which you can discover if an account exists simply by seeing if you
        // can proceede to the next step in the login process.
        if (!password_verify($request->input('password'), $user->password)) {
            return $this->sendFailedLoginResponse($request, $user);
        }

        if ($user->use_totp) {
            $token = Str::random(64);
            $this->cache->put($token, $user->id, CarbonImmutable::now()->addMinutes(5));

            return new JsonResponse([
                'data' => [
                    'complete' => false,
                    'confirmation_token' => $token,
                ],
            ]);
        }

        $this->auth->guard()->login($user, true);

        return $this->sendLoginResponse($user, $request);
    }
}
