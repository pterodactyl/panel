<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use SocialiteProviders\Manager\Config;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class OAuthController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * LoginController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(UserRepositoryInterface $repository)
    {
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

    }
}
