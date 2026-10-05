<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Facades\RateLimiter;
use Pterodactyl\Contracts\Users\UpdatesUserEmails;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Models\User;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final readonly class UpdateUserEmail implements UpdatesUserEmails
{
    private const int THROTTLE_MAX = 3;

    private const int THROTTLE_DECAY = 60 * 60 * 24;

    public function __construct(private UpdatesUsers $users) {}

    /**
     * @return array{user: User, original: string, email: string, changed: bool}
     */
    public function update(User $user, string $email): array
    {
        throw_if(RateLimiter::tooManyAttempts($key = $this->throttleKey($user), self::THROTTLE_MAX), TooManyRequestsHttpException::class, 'Your email address has been changed too many times today. Please try again later.');

        $original = $user->email;
        if (mb_strtolower($original) === mb_strtolower($email)) {
            return ['user' => $user, 'original' => $original, 'email' => $email, 'changed' => false];
        }

        RateLimiter::hit($key, self::THROTTLE_DECAY);

        $user = $this->users->update($user, ['email' => $email]);

        return ['user' => $user, 'original' => $original, 'email' => $email, 'changed' => true];
    }

    private function throttleKey(User $user): string
    {
        return "user:update-email:{$user->uuid}";
    }
}
