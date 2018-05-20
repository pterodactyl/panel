<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;

class RequireTwoFactorAuthentication
{
    const LEVEL_NONE = 0;
    const LEVEL_ADMIN = 1;
    const LEVEL_ALL = 2;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * The names of routes that should be accessible without 2FA enabled.
     *
     * @var array
     */
    protected $except = [
        'account.security',
        'account.security.revoke',
        'account.security.totp',
        'account.security.totp.set',
        'account.security.totp.disable',
        'auth.totp',
        'auth.logout',
    ];

    /**
     * The route to redirect a user to to enable 2FA.
     *
     * @var string
     */
    protected $redirectRoute = 'account.security';

    /**
     * RequireTwoFactorAuthentication constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     */
    public function __construct(AlertsMessageBag $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()) {
            return $next($request);
        }

        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        switch ((int) config('pterodactyl.auth.2fa_required')) {
            case self::LEVEL_ADMIN:
                if (! $request->user()->root_admin || $request->user()->use_totp) {
                    return $next($request);
                }
                break;
            case self::LEVEL_ALL:
                if ($request->user()->use_totp) {
                    return $next($request);
                }
                break;
            case self::LEVEL_NONE:
            default:
                return $next($request);
        }

        $this->alert->danger(trans('auth.2fa_must_be_enabled'))->flash();

        return redirect()->route($this->redirectRoute);
    }
}
