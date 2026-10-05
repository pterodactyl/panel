<?php

declare(strict_types=1);

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Pterodactyl\Contracts\Extensions\HashidsInterface;

/**
 * @method static string encode(mixed ...$numbers)
 * @method static array<int, int> decode(string $hash)
 * @method static int|string|null decodeFirst(string $encoded, ?string $default = null)
 *
 * @see HashidsInterface
 */
class Hashids extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HashidsInterface::class;
    }
}
