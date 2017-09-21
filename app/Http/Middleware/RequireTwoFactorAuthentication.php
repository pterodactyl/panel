<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Krucas\Settings\Settings;
use Prologue\Alerts\AlertsMessageBag;

class RequireTwoFactorAuthentication
{
    const NOBODY = 0;
    const ADMINISTRATORS = 1;
    const USERS = 2;
    const EVERYBODY = 3;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Krucas\Settings\Settings
     */
    protected $settings;

    /**
     * RequireTwoFactorAuthentication constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Krucas\Settings\Settings         $settings
     */
    public function __construct(Settings $settings, AlertsMessageBag $alert)
    {
        $this->settings = $settings;
        $this->alert = $alert;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Ignore non-users
        if (! auth()->check()) {
            return $next($request);
        }

        // All TOTP related routes
        $validRoutes = [
            'account.security',
            'account.security.revoke',
            'account.security.totp',
            'account.security.totp.set',
            'account.security.totp.disable',
            'auth.totp',
            'auth.logout',
        ];

        // Only allow 2FA pages
        if (in_array($request->route()->getName(), $validRoutes)) {
            return $next($request);
        }

        // Get the setting
        $tfa = (int) $this->settings->get('2fa', 0);

        switch ($tfa) {
            case self::NOBODY:
                return $next($request);

            case self::ADMINISTRATORS:
                if (! $request->user()->root_admin) {
                    return $next($request);
                }
                break;

            case self::USERS:
                if ($request->user()->accessLevel === 'subuser') {
                    return $next($request);
                }
                break;

            case self::EVERYBODY:
                if ($request->user()->use_totp) {
                    return $next($request);
                }
                break;
        }

        $this->alert->danger('The administrator has required 2FA to be enabled. You must enable it before you can do any other action.')->flash();

        return redirect()->route('account.security');
    }
}
