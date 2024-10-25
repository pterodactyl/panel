<?php
/**
 * The MERGE storage engine
 */

declare(strict_types=1);

namespace PhpMyAdmin\Engines;

/**
 * The MERGE storage engine
 */
class MrgMyisam extends Merge
{
    /**
     * returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage()
    {
        return 'merge-storage-engine';
    }
}
