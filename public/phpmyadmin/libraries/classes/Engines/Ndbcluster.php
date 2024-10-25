<?php
/**
 * The NDBCLUSTER storage engine
 */

declare(strict_types=1);

namespace PhpMyAdmin\Engines;

use PhpMyAdmin\StorageEngine;

/**
 * The NDBCLUSTER storage engine
 */
class Ndbcluster extends StorageEngine
{
    /**
     * Returns array with variable names related to NDBCLUSTER storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return [
            'ndb_connectstring' => [],
        ];
    }

    /**
     * Returns the pattern to be used in the query for SQL variables
     * related to NDBCLUSTER storage engine
     *
     * @return string  SQL query LIKE pattern
     */
    public function getVariablesLikePattern()
    {
        return 'ndb\\_%';
    }

    /**
     * Returns string with filename for the MySQL help page
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage()
    {
        return 'ndbcluster';
    }
}
