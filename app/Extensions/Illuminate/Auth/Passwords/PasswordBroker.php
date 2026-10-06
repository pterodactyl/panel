<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Illuminate\Auth\Passwords;

use Closure;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Passwords\PasswordBroker as IlluminatePasswordBroker;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;
use SensitiveParameter;

class PasswordBroker extends IlluminatePasswordBroker
{
    /**
     * {@inheritdoc}
     *
     * @param  array<string, string>  $credentials
     */
    public function sendResetLink(#[SensitiveParameter] array $credentials, ?Closure $callback = null): string
    {
        return JsonValueGuard::string($this->timebox->call(function () use ($credentials, $callback): string {
            $user = $this->getUser($credentials);

            if ($user === null) {
                Hash::make(Str::random(64));

                return static::INVALID_USER;
            }

            if ($this->tokens->recentlyCreatedToken($user)) {
                Hash::make(Str::random(64));

                return static::RESET_THROTTLED;
            }

            $token = $this->tokens->create($user);

            if ($callback instanceof Closure) {
                return JsonValueGuard::nullableString($callback($user, $token)) ?? static::RESET_LINK_SENT;
            }

            $user->sendPasswordResetNotification($token);

            $this->events?->dispatch(new PasswordResetLinkSent($user));

            return static::RESET_LINK_SENT;
        }, $this->timeboxDuration));
    }

    /**
     * {@inheritdoc}
     *
     * @param  array<string, string>  $credentials
     */
    protected function validateReset(#[SensitiveParameter] array $credentials): CanResetPasswordContract|string
    {
        $user = $this->getUser($credentials);

        $exists = $this->tokens->exists(
            $user ?? (new User)->forceFill(['email' => $credentials['email'] ?? '']),
            $credentials['token'] ?? '',
        );

        if ($user === null) {
            return static::INVALID_USER;
        }

        if (! $exists) {
            return static::INVALID_TOKEN;
        }

        return $user;
    }
}
