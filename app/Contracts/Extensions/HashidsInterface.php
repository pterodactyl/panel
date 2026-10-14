<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Extensions;

use Hashids\HashidsInterface as VendorHashidsInterface;
use InvalidArgumentException;

interface HashidsInterface extends VendorHashidsInterface
{
    /** @throws InvalidArgumentException */
    public function decodeFirst(string $encoded, ?string $default = null): int|string|null;
}
