<?php

declare(strict_types=1);

namespace Pterodactyl\Data;

use Pterodactyl\Models\User;

/**
 * The outcome of completing a login: either a session was established for the
 * user, or a two-factor checkpoint was issued and no one is signed in yet.
 */
final readonly class LoginResult
{
    private function __construct(
        public User $user,
        public bool $complete,
        public string $intended,
        public ?string $confirmationToken,
    ) {}

    public static function established(User $user, string $intended): self
    {
        return new self($user, true, $intended, null);
    }

    public static function checkpoint(User $user, LoginCheckpoint $checkpoint, string $intended): self
    {
        return new self($user, false, $intended, $checkpoint->token);
    }

    /**
     * The `data` payload the panel's login screens expect from a JSON login endpoint.
     *
     * @return array{complete: false, confirmation_token: string|null}|array{complete: true, intended: string, user: ApiPayload}
     */
    public function toResponseData(): array
    {
        if (! $this->complete) {
            return [
                'complete' => false,
                'confirmation_token' => $this->confirmationToken,
            ];
        }

        return [
            'complete' => true,
            'intended' => $this->intended,
            'user' => $this->user->toVueObject(),
        ];
    }
}
