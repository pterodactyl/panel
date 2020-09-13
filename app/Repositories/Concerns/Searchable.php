<?php

namespace Pterodactyl\Repositories\Concerns;

trait Searchable
{
    /**
     * The search term to use when filtering results.
     *
     * @var string|null
     */
    protected $searchTerm;

    /**
     * Set the search term to use when requesting all records from
     * the model.
     *
     * @param string|null $term
     * @return $this
     */
    public function setSearchTerm(string $term = null)
    {
        if (empty($term)) {
            return $this;
        }

        $clone = clone $this;
        $clone->searchTerm = $term;

        return $clone;
    }
}
