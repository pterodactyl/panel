<?php

namespace Pterodactyl\Repositories\Concerns;

trait Searchable
{
    /**
     * The search term to use when filtering results.
     *
     * @var null|string
     */
    protected $searchTerm;

    /**
     * Set the search term.
     *
     * @param string|null $term
     * @return $this
     * @deprecated
     */
    public function search($term)
    {
        return $this->setSearchTerm($term);
    }

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

    /**
     * Determine if a valid search term is set on this repository.
     *
     * @return bool
     */
    public function hasSearchTerm(): bool
    {
        return ! empty($this->searchTerm);
    }

    /**
     * Return the search term.
     *
     * @return string|null
     */
    public function getSearchTerm()
    {
        return $this->searchTerm;
    }
}
