<?php
/**
 * The MEMORY (HEAP) storage engine
 */

declare(strict_types=1);

namespace PhpMyAdmin\Engines;

use PhpMyAdmin\StorageEngine;

/**
 * The MEMORY (HEAP) storage engine
 */
class Memory extends StorageEngine
{
    /**
     * Returns array with variable names dedicated to MEMORY storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return [
            'max_heap_table_size' => ['type' => StorageEngine::DETAILS_TYPE_SIZE],
        ];
    }
}
