<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OAuthController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * LoginController constructor.
     *
     * @param \Illuminate\Auth\AuthManager $auth
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(AuthManager $auth, UserRepositoryInterface $repository)
    {
        $this->auth = $auth;
        $this->repository = $repository;
    }

    /**
     * Redirect to the provider's website
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirect(Request $request): RedirectResponse
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

        return Socialite::with($driver)->redirect();
    }

    /**
     * Validate and login OAuth user.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function callback(Request $request): RedirectResponse
    {
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
            $user = $this->repository->findFirstWhere([['oauth->'. $driver, $oauthUser->getId()]]);
        } catch (RecordNotFoundException $e) {
            return redirect()->route('auth.login');
        }

        $this->auth->guard()->login($user, true);

        return redirect('/');
    }
}
