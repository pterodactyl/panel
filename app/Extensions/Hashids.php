<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions;

use Hashids\Hashids as VendorHashids;
use Illuminate\Support\Arr;
use Pterodactyl\Contracts\Extensions\HashidsInterface;

class Hashids extends VendorHashids implements HashidsInterface
{
    public function decodeFirst(string $encoded, ?string $default = null): int|string|null
    {
        $result = $this->decode($encoded);

        return Arr::first($result, null, $default);
    }
}
