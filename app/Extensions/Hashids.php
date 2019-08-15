<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Extensions;

use Illuminate\Support\Arr;
use Hashids\Hashids as VendorHashids;
use Pterodactyl\Contracts\Extensions\HashidsInterface;

class Hashids extends VendorHashids implements HashidsInterface
{
    /**
     * {@inheritdoc}
     */
    public function decodeFirst($encoded, $default = null)
    {
        $result = $this->decode($encoded);
        if (! is_array($result)) {
            return $default;
        }

        return Arr::first($result, null, $default);
    }
}
