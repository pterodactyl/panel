<?php

namespace Pterodactyl\Extensions;

use Illuminate\Support\Arr;
use Hashids\Hashids as VendorHashids;
use Pterodactyl\Contracts\Extensions\HashidsInterface;

class Hashids extends VendorHashids implements HashidsInterface
{
    public function decodeFirst(string $encoded, ?string $default = null): mixed
    {
        $result = $this->decode($encoded);

        return Arr::first($result, null, $default);
    }
}
