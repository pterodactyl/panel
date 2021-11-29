<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Prologue\Alerts\AlertsMessageBag;

class RequireOAuthAuthentication
{
    public const LEVEL_NONE = 0;
    public const LEVEL_USER = 1;
    public const LEVEL_ADMIN = 2;
    public const LEVEL_ALL = 3;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * The route to redirect a user to to enable OAuth.
     *
     * @var string
     */
    protected $redirectRoute = 'account';

    /**
     * RequireTwoFactorAuthentication constructor.
     */
    public function __construct(AlertsMessageBag $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return $next($request);
        }

        $current = $request->route()->getName();
        if (in_array($current, ['auth', 'account']) || Str::startsWith($current, ['auth.', 'account.'])) {
            return $next($request);
        }

        switch ((int) config('oauth.required')) {
            case self::LEVEL_ALL:
                if ($this->hasActiveOAuthProvider($request->user())) {
                    return $next($request);
                }
                break;
            case self::LEVEL_ADMIN:
                if (!$request->user()->root_admin || $this->hasActiveOAuthProvider($request->user())) {
                    return $next($request);
                }
                break;
            case self::LEVEL_USER:
                if ($request->user()->root_admin || $this->hasActiveOAuthProvider($request->user())) {
                    return $next($request);
                }
                break;
            case self::LEVEL_NONE:
            default:
                return $next($request);
        }

        $this->alert->danger(trans('auth.oauth_must_be_enabled'))->flash();

        return redirect()->route($this->redirectRoute);
    }

    private function hasActiveOAuthProvider(User $user)
    {
        $userDrivers = json_decode($user->oauth, true);

        $drivers = json_decode(app('config')->get('oauth.drivers'), true);

        foreach ($drivers as $driver => $options) {
            if ($options['enabled'] && array_has($userDrivers, $driver)) {
                return true;
            }
        }

        return false;
    }
}
