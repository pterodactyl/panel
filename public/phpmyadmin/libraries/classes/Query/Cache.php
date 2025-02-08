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
    /** @var array[] Table data cache */
    private $tableCache = [];

    /**
     * Caches table data so Table does not require to issue
     * SHOW TABLE STATUS again
     *
     * @param mixed[][] $tables information for tables of some databases
     */
    public function cacheTableData(string $database, array $tables): void
    {
        // Note: This function must not use array_merge because numerical indices must be preserved.
        // When an entry already exists for the database in cache, we merge the incoming data with existing data.
        // The union operator appends elements from right to left unless they exists on the left already.
        // Doing the union with incoming data on the left ensures that when we reread table status from DB,
        // we overwrite whatever was in cache with the new data.

        if (isset($this->tableCache[$database])) {
            $this->tableCache[$database] = $tables + $this->tableCache[$database];
        } else {
            $this->tableCache[$database] = $tables;
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
