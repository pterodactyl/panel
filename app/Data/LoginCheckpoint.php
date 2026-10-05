<?php

declare(strict_types=1);

namespace Pterodactyl\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * A login that has passed its first factor and is waiting for the account's
 * two-factor challenge to be answered within the same session.
 */
final readonly class LoginCheckpoint
{
    private const int LIFETIME_MINUTES = 5;

    public function __construct(
        public int $userId,
        public string $token,
        public CarbonInterface $expiresAt,
    ) {}

    public static function issue(int $userId): self
    {
        return new self($userId, Str::random(64), CarbonImmutable::now()->addMinutes(self::LIFETIME_MINUTES));
    }

    /**
     * Whether a value read back from the session is a checkpoint that can
     * still be confirmed. Anything else is treated as if none had been issued.
     *
     * @phpstan-assert-if-true self $value
     */
    public static function isPending(mixed $value): bool
    {
        return $value instanceof self && ! $value->expiresAt->isBefore(CarbonImmutable::now());
    }

    /** Compare a submitted confirmation token against this checkpoint in constant time. */
    public function matches(string $token): bool
    {
        return hash_equals($this->token, $token);
    }
}
