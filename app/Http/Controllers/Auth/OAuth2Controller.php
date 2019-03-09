<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class OAuth2Controller extends Controller
{
    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    protected $creationService;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    private $hasher;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

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
     * @param \Pterodactyl\Services\Users\UserCreationService           $creationService
     * @param \Illuminate\Contracts\Hashing\Hasher                      $hasher
     * @param \Illuminate\Auth\AuthManager                              $auth
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     * @param \Pterodactyl\Services\Users\UserUpdateService             $updateService
     */
    public function __construct(UserCreationService $creationService, Hasher $hasher, AuthManager $auth, UserRepositoryInterface $repository, UserUpdateService $updateService)
    {
        $this->creationService = $creationService;
        $this->hasher = $hasher;
        $this->auth = $auth;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Obtain the user information and login
     * or redirect the user to the authentication page.
     *
     * @param string $driver
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function login($driver= null)
    {
        // If feature is not enabled or user is already signed in redirect or abort
        if ($this->auth->guard()->check()) {
            return redirect()->route('index');
        }
        if (! env('OAUTH2')) {
            abort(404);
        }

        // Get the current OAuth2 user else redirect to auth page
        if(!session()->has('oauth2_driver')) return $this->redirectToProvider($driver);
        try {
            $socialite_user = Socialite::driver(session()->get('oauth2_driver'))->user();
        } catch (\Exception $exception) {
            return $this->redirectToProvider($driver);
        }

        // The user's Id.
        $oauth2_id = $socialite_user->getId();

        try {
            // Try to get the user
            $user = User::where('oauth2_id',
                'LIKE',
                '%' . Str::upper(session()->get('oauth2_driver')) . ':=>:' . $oauth2_id . '%')->firstOrFail();

            // Login
            $this->auth->guard()->login($user);
            if ($this->auth->guard()->check()) {
                return redirect()->route('index');
            }
        } catch (\Exception $e) {}

        // Invalid Login
        $errors = new MessageBag(['user' => [__('auth.failed')]]);
        return redirect()->route('auth.login')
            ->withErrors($errors);
    }

    /**
     * Redirect the user to the authentication page.
     *
     * @param string $driver
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($driver)
    {
        // Check if the driver exists and is enabled else use the default one
        $driver = is_null($driver) ? env('OAUTH2_DEFAULT_DRIVER') : $driver;
        $driver = Arr::has(config('services'), $driver) ? $driver : env('OAUTH2_DEFAULT_DRIVER');
        $driver = Arr::has(preg_split('~,~', env('OAUTH2_ENABLED_DRIVERS')), $driver) ? $driver : env('OAUTH2_DEFAULT_DRIVER');

        // Save the driver the user's using
        session()->put('oauth2_driver', $driver);
        session()->save();

        return Socialite::driver($driver)
            ->with(unserialize(env(Str::upper($driver).'_OAUTH2_EXTRA_PARAMETERS', 'a:0:{}')))
            ->scopes(preg_split('~,~', env(Str::upper($driver).'_OAUTH2_SCOPES', 'email')))
            ->redirect();
    }
}
