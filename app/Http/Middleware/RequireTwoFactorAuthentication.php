<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Http\TwoFactorAuthRequiredException;
use Pterodactyl\Facades\Alert;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorAuthentication
{
    use ResolvesRequestContext;

    public const int LEVEL_NONE = 0;

    public const int LEVEL_ADMIN = 1;

    public const int LEVEL_ALL = 2;

    /**
     * The route to redirect a user to enable 2FA.
     */
    protected string $redirectRoute = '/account';

    /**
     * Check the user state on the incoming request to determine if they should be allowed to
     * proceed or not. This checks if the Panel is configured to require 2FA on an account in
     * order to perform actions. If so, we check the level at which it is required (all users
     * or just admins) and then check if the user has enabled it for their account.
     *
     * @throws TwoFactorAuthRequiredException
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $uri = mb_rtrim($request->getRequestUri(), '/').'/';
        $current = $this->resolvedRoute($request)->getName() ?? '';

        if (! $user || Str::startsWith($uri, ['/auth/']) || Str::startsWith($current, ['auth.', 'account.'])) {
            return $next($request);
        }

        $configuredLevel = filter_var(config('pterodactyl.auth.2fa_required'), FILTER_VALIDATE_INT);
        $level = $configuredLevel === false ? self::LEVEL_NONE : $configuredLevel;
        // If this setting is not configured, or the user is already using 2FA then we can just
        // send them right through, nothing else needs to be checked.
        //
        // If the level is set as admin and the user is not an admin, pass them through as well.
        if ($level === self::LEVEL_NONE || $user->use_totp) {
            return $next($request);
        }

        if ($level === self::LEVEL_ADMIN && ! $user->root_admin) {
            return $next($request);
        }

        // For API calls return an exception which gets rendered nicely in the API response.
        throw_if($request->isJson() || Str::startsWith($uri, '/api/'), TwoFactorAuthRequiredException::class);

        Alert::danger(trans('auth.2fa_must_be_enabled'))->flash();

        return redirect()->to($this->redirectRoute);
    }
}
