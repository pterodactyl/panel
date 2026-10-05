<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Data\LoginResult;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Helpers\ThrottlesLogins;

abstract class AbstractLoginController extends Controller
{
    use ThrottlesLogins;

    /**
     * Get the failed login response instance.
     *
     * @throws DisplayException
     */
    protected function sendFailedLoginResponse(Request $request, ?Authenticatable $user = null, ?string $message = null): never
    {
        $this->incrementLoginAttempts($request);
        $login = $request->string('user')->toString();
        $this->fireFailedLoginEvent($user, [
            $this->getField($login) => $login,
        ]);

        if ($request->route()?->named('auth.login-checkpoint')) {
            throw new DisplayException($message ?? trans('auth.two_factor.checkpoint_failed'));
        }

        throw new DisplayException(trans('auth.failed'));
    }

    /**
     * Send the response for a completed login step. The throttle is only
     * reset once a session exists, so a pending two-factor checkpoint keeps
     * counting failed attempts.
     */
    protected function sendLoginResponse(LoginResult $result, Request $request): JsonResponse
    {
        if ($result->complete) {
            $this->clearLoginAttempts($request);
        }

        return new JsonResponse(['data' => $result->toResponseData()]);
    }

    /**
     * Determine if the user is logging in using an email or username.
     */
    protected function getField(?string $input = null): string
    {
        return ($input && str_contains($input, '@')) ? 'email' : 'username';
    }

    /**
     * Fire a failed login event.
     *
     * @param  array<string, string>  $credentials
     */
    protected function fireFailedLoginEvent(?Authenticatable $user = null, array $credentials = []): void
    {
        Event::dispatch(new Failed('auth', $user, $credentials));
    }
}
