<?php
/**
 * The binary log storage engine
 */

declare(strict_types=1);

namespace PhpMyAdmin\Engines;

use PhpMyAdmin\StorageEngine;

/**
 * The binary log storage engine
 */
class Binlog extends StorageEngine
{
    /**
     * Returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage()
    {
        return 'binary-log';
    }
}
