<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository\Attributes;

interface SearchableInterface
{
    /**
     * Filter results by search term.
     *
     * @param string $term
     * @return $this
     */
    public function search($term);
}
