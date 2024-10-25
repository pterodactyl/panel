<?php
/**
 * Parse and analyse a SQL query
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\SqlParser\Utils\Query;

use function count;
use function strcasecmp;

/**
 * PhpMyAdmin\ParseAnalyze class
 */
class ParseAnalyze
{
    /**
     * Calls the parser on a query
     *
     * @param string $sqlQuery the query to parse
     * @param string $db       the current database
     *
     * @return array
     */
    public static function sqlQuery($sqlQuery, $db)
    {
        // @todo: move to returned results (also in all the calling chain)
        $GLOBALS['unparsed_sql'] = $sqlQuery;

        // Get details about the SQL query.
        $analyzedSqlResults = Query::getAll($sqlQuery);

        $table = '';

        // If the targeted table (and database) are different than the ones that is
        // currently browsed, edit `$db` and `$table` to match them so other elements
        // (page headers, links, navigation panel) can be updated properly.
        if (! empty($analyzedSqlResults['select_tables'])) {
            // Previous table and database name is stored to check if it changed.
            $previousDb = $db;

            if (count($analyzedSqlResults['select_tables']) > 1) {

                /**
                 * @todo if there are more than one table name in the Select:
                 * - do not extract the first table name
                 * - do not show a table name in the page header
                 * - do not display the sub-pages links)
                 */
                $table = '';
            } else {
                $table = $analyzedSqlResults['select_tables'][0][0];
                if (! empty($analyzedSqlResults['select_tables'][0][1])) {
                    $db = $analyzedSqlResults['select_tables'][0][1];
                }
            }

            // There is no point checking if a reload is required if we already decided
            // to reload. Also, no reload is required for AJAX requests.
            $response = ResponseRenderer::getInstance();
            if (empty($analyzedSqlResults['reload']) && ! $response->isAjax()) {
                // NOTE: Database names are case-insensitive.
                $analyzedSqlResults['reload'] = strcasecmp($db, $previousDb) != 0;
            }
        }

        return [
            $analyzedSqlResults,
            $db,
            $table,
        ];
    }
}
