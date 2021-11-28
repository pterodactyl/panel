<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class OAuthController extends Controller
{
    protected AuthManager $auth;

    private UserUpdateService $updateService;

    private UserRepositoryInterface $repository;

    /**
     * The route to redirect a user once linked with the OAuth provider or if the provider doesn't exist.
     *
     * @var string
     */
    protected string $redirectRoute = 'account';

    /**
     * LoginController constructor.
     *
     * @param AuthManager $auth
     * @param UserUpdateService $updateService
     * @param UserRepositoryInterface $repository
     */
    public function __construct(AuthManager $auth, UserUpdateService $updateService, UserRepositoryInterface $repository)
    {
        $this->auth = $auth;
        $this->updateService = $updateService;
        $this->repository = $repository;
    }

    /**
     * Redirect to the provider's website
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function redirect(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (!app('config')->get('pterodactyl.auth.oauth.enabled')) {
            throw new NotFoundHttpException();
        }

        $drivers = json_decode(app('config')->get('pterodactyl.auth.oauth.drivers'), true);
        $driver = $request->get('driver');

        if ($driver == null || !array_has($drivers, $driver) || !$drivers[$driver]['enabled']) {
            return redirect()->route('auth.login');
        }

        // Dirty hack
        // Can't use SocialiteProviders\Manager\Config since all providers are hardcoded for services.php
        config(['services.' . $driver => array_merge(
            array_only($drivers[$driver], ['client_id', 'client_secret']),
            ['redirect' => route('oauth.callback')]
        )]);

        $request->session()->put('oauth_driver', $driver);

        return Socialite::driver($driver)->redirect();
    }

    /**
     * Validate and login OAuth user.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RecordNotFoundException
     * @throws DataValidationException
     * @throws Throwable
     */
    protected function callback(Request $request): RedirectResponse
    {
        // If logged in link provider to user
        if ($request->user() != null) {
            return $this->link($request);
        }

        $driver = $request->session()->pull('oauth_driver');

        if (empty($driver)) {
            return redirect()->route('auth.login');
        }

        $drivers = json_decode(app('config')->get('pterodactyl.auth.oauth.drivers'), true);

        // Dirty hack
        // Can't use SocialiteProviders\Manager\Config since all providers are hardcoded for services.php
        config(['services.' . $driver => array_merge(
            array_only($drivers[$driver], ['client_id', 'client_secret']),
            ['redirect' => route('oauth.callback')]
        )]);

        $oauthUser = Socialite::driver($driver)->user();

        try {
            $user = $this->repository->findFirstWhere([['oauth->' . $driver, $oauthUser->getId()]]);
        } catch (RecordNotFoundException $e) {
            return redirect()->route('auth.login');
        }

        $this->auth->guard()->login($user, true);

        return redirect('/');
    }


    /**
     * Link OAuth id to user
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    protected function link(Request $request): RedirectResponse
    {
        $driver = $request->session()->pull('oauth_linking');

        if (empty($driver)) {
            return redirect($this->redirectRoute);
        }

        $drivers = json_decode(app('config')->get('pterodactyl.auth.oauth.drivers'), true);

        // Dirty hack
        // Can't use SocialiteProviders\Manager\Config since all providers are hardcoded for services.php
        config(['services.' . $driver => array_merge(
            array_only($drivers[$driver], ['client_id', 'client_secret']),
            ['redirect' => route('oauth.callback')]
        )]);

        $oauthUser = Socialite::driver($driver)->user();

        $oauth = json_decode($request->user()->oauth, true);

        $oauth[$driver] = $oauthUser->getId();

        $this->updateService->handle($request->user(), ['oauth' => json_encode($oauth)]);

        return redirect($this->redirectRoute);
    }
}
