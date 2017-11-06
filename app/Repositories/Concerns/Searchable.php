<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Concerns;

trait Searchable
{
    /**
     * The term to search.
     *
     * @var bool|string
     */
    protected $searchTerm = false;

    /**
     * Perform a search of the model using the given term.
     *
     * @param string $term
     * @return $this
     */
    public function search($term)
    {
        if (empty($term)) {
            return $this;
        }

        $clone = clone $this;
        $clone->searchTerm = $term;

        return $clone;
    }
}
