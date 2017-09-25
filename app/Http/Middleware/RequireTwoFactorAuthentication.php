<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Middleware;

use Closure;
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
    protected $alert;

    /**
     * @var \Krucas\Settings\Settings
     */
    protected $settings;

    /**
     * All TOTP related routes.
     *
     * @var array
     */
    protected $ignoreRoutes = [
            'account.security',
            'account.security.revoke',
            'account.security.totp',
            'account.security.totp.set',
            'account.security.totp.disable',
            'auth.totp',
            'auth.logout',
    ];

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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Ignore non-users
        if (! $request->user()) {
            return $next($request);
        }

        // Skip the 2FA pages
        if (in_array($request->route()->getName(), $this->ignoreRoutes)) {
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

        $this->alert->danger('The administrator has required 2FA to be enabled. You must enable it before you can do any other action.')->flash();

        return redirect()->route('account.security');
    }
}
