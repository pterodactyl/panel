<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\Util;

use function array_shift;
use function count;
use function is_array;

/**
 * Handles caching results
 */
class Cache
{
    /** @var array Table data cache */
    private $tableCache = [];

    /**
     * Caches table data so Table does not require to issue
     * SHOW TABLE STATUS again
     *
     * @param array       $tables information for tables of some databases
     * @param string|bool $table  table name
     */
    public function cacheTableData(array $tables, $table): void
    {
        // Note: I don't see why we would need array_merge_recursive() here,
        // as it creates double entries for the same table (for example a double
        // entry for Comment when changing the storage engine in Operations)
        // Note 2: Instead of array_merge(), simply use the + operator because
        //  array_merge() renumbers numeric keys starting with 0, therefore
        //  we would lose a db name that consists only of numbers

        foreach ($tables as $one_database => $_) {
            if (isset($this->tableCache[$one_database])) {
                // the + operator does not do the intended effect
                // when the cache for one table already exists
                if ($table && isset($this->tableCache[$one_database][$table])) {
                    unset($this->tableCache[$one_database][$table]);
                }

                $this->tableCache[$one_database] += $tables[$one_database];
            } else {
                $this->tableCache[$one_database] = $tables[$one_database];
            }
        }
    }

    /**
     * Set an item in table cache using dot notation.
     *
     * @param array|null $contentPath Array with the target path
     * @param mixed      $value       Target value
     */
    public function cacheTableContent(?array $contentPath, $value): void
    {
        $loc = &$this->tableCache;

        if (! isset($contentPath)) {
            $loc = $value;

            return;
        }

        while (count($contentPath) > 1) {
            $key = array_shift($contentPath);

            // If the key doesn't exist at this depth, we will just create an empty
            // array to hold the next value, allowing us to create the arrays to hold
            // final values at the correct depth. Then we'll keep digging into the
            // array.
            if (! isset($loc[$key]) || ! is_array($loc[$key])) {
                $loc[$key] = [];
            }

            $loc = &$loc[$key];
        }

        $loc[array_shift($contentPath)] = $value;
    }

    /**
     * Get a cached value from table cache.
     *
     * @param array $contentPath Array of the name of the target value
     * @param mixed $default     Return value on cache miss
     *
     * @return mixed cached value or default
     */
    public function getCachedTableContent(array $contentPath, $default = null)
    {
        return Util::getValueByKey($this->tableCache, $contentPath, $default);
    }

    public function getCache(): array
    {
        return $this->tableCache;
    }

    public function clearTableCache(): void
    {
        $this->tableCache = [];
    }
}
