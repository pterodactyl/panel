<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Extensions;

use Hashids\HashidsInterface as VendorHashidsInterface;

interface HashidsInterface extends VendorHashidsInterface
{
    /**
     * Decode an encoded hashid and return the first result.
     *
     * @param string $encoded
     * @param null $default
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function decodeFirst($encoded, $default = null);
}
