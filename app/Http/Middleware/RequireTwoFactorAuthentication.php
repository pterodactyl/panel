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
use Krucas\Settings\Settings;
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
     * @var \Krucas\Settings\Settings
     */
    private $settings;

    /**
     * The names of routes that should be accessable without 2FA enabled.
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
     * @param \Krucas\Settings\Settings         $settings
     */
    public function __construct(AlertsMessageBag $alert, Settings $settings)
    {
        $this->alert = $alert;
        $this->settings = $settings;
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
        // Ignore non-users
        if (! $request->user()) {
            return $next($request);
        }

        // Skip the 2FA pages
        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        // Get the setting
        switch ((int) $this->settings->get('2fa', 0)) {
            case self::LEVEL_NONE:
                return $next($request);

            case self::LEVEL_ADMIN:
                if (! $request->user()->root_admin) {
                    return $next($request);
                }
                break;

            case self::LEVEL_ALL:
                if ($request->user()->use_totp) {
                    return $next($request);
                }
                break;
        }

        $this->alert->danger(trans('auth.2fa_must_be_enabled'))->flash();

        return redirect()->route($this->redirectRoute);
    }
}
