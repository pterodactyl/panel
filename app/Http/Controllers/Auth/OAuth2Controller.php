<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\MessageBag;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Hashing\Hasher;
use Laravel\Socialite\Facades\Socialite;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Helpers\OAuth2Providers;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OAuth2Controller extends Controller
{
    use OAuth2Providers;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    protected $creationService;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    private $hasher;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    protected $updateService;

    /**
     * OAuth2Controller constructor.
     *
     * @param AlertsMessageBag $alert
     * @param \Illuminate\Auth\AuthManager $auth
     * @param ConfigRepository $config
     * @param \Pterodactyl\Services\Users\UserCreationService $creationService
     * @param \Illuminate\Contracts\Hashing\Hasher $hasher
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     * @param \Pterodactyl\Services\Users\UserUpdateService $updateService
     */
    public function __construct(AlertsMessageBag $alert,
                                AuthManager $auth,
                                ConfigRepository $config,
                                UserCreationService $creationService,
                                Hasher $hasher,
                                UserRepositoryInterface $repository,
                                UserUpdateService $updateService)
    {
        $this->alert = $alert;
        $this->auth = $auth;
        $this->config = $config;
        $this->creationService = $creationService;
        $this->hasher = $hasher;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Login the user or link his account to the oauth2 account
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // If this feature is not enabled or the user is already signed in and isn't linking a new account redirect or abort
        if ($this->auth->guard()->check() and ! $request->session()->has('link_oauth2_driver')) {
            return redirect()->route('index');
        }
        if (! $this->config->get('oauth2.enabled')) {
            throw new NotFoundHttpException();
        }

        // If linking a new provider form the account page
        if ($request->session()->has('link_oauth2_driver')) {
            $sessionDriver = $request->session()->get('link_oauth2_driver');
            $request->session()->forget('link_oauth2_driver');

            // Check if authenticated
            if (! $this->auth->guard()->check()) {
                return redirect()->route('auth.login')
                    ->withErrors(new MessageBag(['user' => [__('auth.failed')]]));
            }

            $user = $this->auth->guard()->user();

            // Get the user info from OAuth2
            try {
                $socialiteUser = Socialite::driver($sessionDriver)->user();
            } catch (\Exception $exception) {
                $this->alert->danger(trans('base.account.oauth2_link_failed'))->flash();

                return redirect()->route('account');
            }

            $oauth2SocialiteId = $socialiteUser->getId();

            $oauth2_id = $user->oauth2_id;

            if (strpos($oauth2_id, $sessionDriver . ':')) {
                // Resolves as <provider>:<ID>
                preg_replace($sessionDriver . ':.[^,]+',
                    $sessionDriver . ':' . $oauth2SocialiteId,
                    $oauth2_id);
            } else {
                $oauth2_id .= ',' . $sessionDriver . ':' . $oauth2SocialiteId;
            }

            try {
                $this->updateService->handle($user, compact('oauth2_id'));
            } catch (DataValidationException | RecordNotFoundException $e) {
                $this->alert->danger(trans('base.account.oauth2_link_failed'))->flash();
                return redirect()->route('account');
            }

            $this->alert->success(trans('base.account.details_updated'))->flash();

            return redirect()->route('account');
        }

        // Get the current OAuth2 user else redirect to auth page
        if (! $request->session()->has('oauth2_driver')) {
            return redirect()->route('auth.login');
        }

        $sessionDriver = $request->session()->get('oauth2_driver');
        $request->session()->forget('oauth2_driver');

        try {
            $socialiteUser = Socialite::driver($sessionDriver)->user();
        } catch (\Exception $exception) {
            //Blind catch used if the user cant be resolved using the provided info
            return redirect()->route('auth.login');
        }

        // The user's Id.
        $oauth2_id = $socialiteUser->getId();

        try {
            // Try to get the user
            $user = $this->repository->findFirstWhere([['oauth2_id', 'LIKE', '%' . $sessionDriver . ':' . $oauth2_id . '%']]);

            // Login
            $this->auth->guard()->login($user);
            if ($this->auth->guard()->check()) {
                return redirect()->route('index');
            }
        } catch (\Exception $e) {
            //Blind catch used if the user doesnt exist or if a DB error happened
            //Nothing here since the code is already below
        }

        // Invalid Login
        return redirect()->route('auth.login')
            ->withErrors(new MessageBag(['user' => [__('auth.failed')]]));
    }

    /**
     * Redirect the user to the authentication page.
     *
     * @param Request $request
     * @param string $driver
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function redirect(Request $request, $driver = null)
    {
        // If this feature is not enabled or the user is already signed in and isn't linking a new account redirect or abort
        if ($this->auth->guard()->check() and ! $request->session()->has('link_oauth2_driver')) {
            return redirect()->route('index');
        }
        if (! $this->config->get('oauth2.enabled')) {
            throw new NotFoundHttpException();
        }

        // Check if the driver exists and is enabled else use the default one
        $driver = is_null($driver) ? $this->config->get('oauth2.default_driver') : $driver;
        $driver = Arr::has($this->getEnabledProviderSettings(), $driver) ? $driver : $this->config->get('oauth2.default_driver');

        // Save the driver the user's using
        $request->session()->put('oauth2_driver', $driver);
        $request->session()->save();

        return Socialite::driver($driver)
            ->scopes(preg_split('~,~', $this->config->get('oauth2.providers.' . $driver . '.scopes')))
            ->redirect();
    }
}
