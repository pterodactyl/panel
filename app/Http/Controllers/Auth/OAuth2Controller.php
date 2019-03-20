<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Support\Arr;
use Illuminate\Auth\AuthManager;
use Laravel\Socialite\Facades\Socialite;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Helpers\OAuth2Providers;

class OAuth2Controller extends Controller
{
    use OAuth2Providers;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

    /**
     * OAuth2Controller constructor.
     *
     * @param \Illuminate\Auth\AuthManager $auth
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Redirect the user to the authentication page.
     *
     * @param string $driver
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function redirect($driver = null)
    {
        // If this feature is not enabled or the user is already signed in and isn't linking a new account redirect or abort
        if ($this->auth->guard()->check() and ! session()->has('link_oauth2_driver')) {
            return redirect()->route('index');
        }
        if (! config('oauth2.enabled')) {
            abort(404);
        }

        // Check if the driver exists and is enabled else use the default one
        $driver = is_null($driver) ? config('oauth2.default_driver') : $driver;
        $driver = Arr::has($this->getEnabledProviderSettings(), $driver) ? $driver : config('oauth2.default_driver');

        // Save the driver the user's using
        session()->put('oauth2_driver', $driver);
        session()->save();

        return Socialite::driver($driver)
            ->scopes(preg_split('~,~', config('oauth2.providers.' . $driver . '.scopes')))
            ->redirect();
    }
}
