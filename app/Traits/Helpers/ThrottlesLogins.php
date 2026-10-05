<?php

declare(strict_types=1);

namespace Pterodactyl\Traits\Helpers;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login rate limiting, previously provided by laravel/ui's ThrottlesLogins trait.
 * Limits come from the "auth.lockout" configuration, keyed on the login
 * identifier and request IP.
 */
trait ThrottlesLogins
{
    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        return RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxLoginAttempts());
    }

    protected function incrementLoginAttempts(Request $request): void
    {
        RateLimiter::hit($this->throttleKey($request), $this->lockoutDecayMinutes() * 60);
    }

    protected function clearLoginAttempts(Request $request): void
    {
        RateLimiter::clear($this->throttleKey($request));
    }

    protected function fireLockoutEvent(Request $request): void
    {
        event(new Lockout($request));
    }

    /**
     * @throws ValidationException
     */
    protected function sendLockoutResponse(Request $request): never
    {
        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            $this->throttleUsernameField() => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }

    protected function throttleKey(Request $request): string
    {
        $login = $request->string($this->throttleUsernameField())->toString();

        return Str::transliterate(Str::lower($login).'|'.($request->ip() ?? 'unknown'));
    }

    protected function throttleUsernameField(): string
    {
        return 'email';
    }

    protected function maxLoginAttempts(): int
    {
        return config()->integer('auth.lockout.attempts', 3);
    }

    /** How long a lockout lasts, in minutes. */
    protected function lockoutDecayMinutes(): int
    {
        return config()->integer('auth.lockout.time', 2);
    }
}
