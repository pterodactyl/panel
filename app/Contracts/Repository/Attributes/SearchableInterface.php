<?php

namespace Pterodactyl\Contracts\Repository\Attributes;

interface SearchableInterface
{
    /**
     * Set the search term.
     *
     * @param string|null $term
     * @return $this
     * @deprecated
     */
    public function search($term);

    /**
     * Set the search term to use when requesting all records from
     * the model.
     *
     * @param string|null $term
     * @return $this
     */
    public function setSearchTerm(string $term = null);

    /**
     * Determine if a valid search term is set on this repository.
     *
     * @return bool
     */
    public function hasSearchTerm(): bool;

    /**
     * Return the search term.
     *
     * @return string|null
     */
    public function getSearchTerm();
}
