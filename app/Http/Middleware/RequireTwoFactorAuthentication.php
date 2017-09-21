<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Krucas\Settings\Settings;
use Prologue\Alerts\AlertsMessageBag;

class RequireTwoFactorAuthentication
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Krucas\Settings\Settings
     */
    protected $settings;

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
        if (!auth()->check()) {
            return $next($request);
        }

        $validRoutes = [
            'account.security',
            'account.security.revoke',
            'account.security.totp',
            'account.security.totp.set',
            'account.security.totp.disable',
            'auth.totp',
            'auth.logout',
        ];

        // Only allow 2FA page
        if (in_array($request->route()->getName(), $validRoutes)) {
            return $next($request);
        }

        // Get the setting
        $tfa = (int) $this->settings->get('2fa', 0);

        switch ($tfa) {
            // Admins Only
            case 1:
                if (!$request->user()->root_admin) {
                    break;
                }

            // Everybody
            case 2:
                if (!$request->user()->use_totp) {

                    $this->alert->danger('The administrator has required 2FA to be enabled. You must enable it before you can do any other action.')->flash();
                    return redirect()->route('account.security');
                }

            // Nobody
            case 0:
            default:
                return $next($request);
        }
    }
}
