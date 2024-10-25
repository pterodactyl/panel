<?php
/**
 * Handles DB Multi-table query
 */

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;

use function array_keys;
use function md5;

/**
 * Class to handle database Multi-table querying
 */
class MultiTableQuery
{
    /**
     * DatabaseInterface instance
     *
     * @var DatabaseInterface
     */
    private $dbi;

    /**
     * Database name
     *
     * @var string
     */
    private $db;

    /**
     * Default number of columns
     *
     * @var int
     */
    private $defaultNoOfColumns;

    /**
     * Table names
     *
     * @var array
     */
    private $tables;

    /** @var Template */
    public $template;

    /**
     * @param DatabaseInterface $dbi                DatabaseInterface instance
     * @param Template          $template           Template instance
     * @param string            $dbName             Database name
     * @param int               $defaultNoOfColumns Default number of columns
     */
    public function __construct(
        DatabaseInterface $dbi,
        Template $template,
        $dbName,
        $defaultNoOfColumns = 3
    ) {
        $this->dbi = $dbi;
        $this->db = $dbName;
        $this->defaultNoOfColumns = $defaultNoOfColumns;

        $this->template = $template;

        $this->tables = $this->dbi->getTables($this->db);
    }

    /**
     * Get Multi-Table query page HTML
     *
     * @return string Multi-Table query page HTML
     */
    public function getFormHtml()
    {
        $tables = [];
        foreach ($this->tables as $table) {
            $tables[$table]['hash'] = md5($table);
            $tables[$table]['columns'] = array_keys(
                $this->dbi->getColumns($this->db, $table)
            );
        }

        return $this->template->render('database/multi_table_query/form', [
            'db' => $this->db,
            'tables' => $tables,
            'default_no_of_columns' => $this->defaultNoOfColumns,
        ]);
    }

    /**
     * Displays multi-table query results
     *
     * @param string $sqlQuery The query to parse
     * @param string $db       The current database
     */
    public static function displayResults($sqlQuery, $db): string
    {
        global $dbi;

        [, $db] = ParseAnalyze::sqlQuery($sqlQuery, $db);

        $goto = Url::getFromRoute('/database/multi-table-query');

        $relation = new Relation($dbi);
        $sql = new Sql(
            $dbi,
            $relation,
            new RelationCleanup($dbi, $relation),
            new Operations($dbi, $relation),
            new Transformations(),
            new Template()
        );

        return $sql->executeQueryAndSendQueryResponse(
            null, // analyzed_sql_results
            false, // is_gotofile
            $db, // db
            null, // table
            null, // find_real_end
            null, // sql_query_for_bookmark - see below
            null, // extra_data
            null, // message_to_show
            null, // sql_data
            $goto, // goto
            null, // disp_query
            null, // disp_message
            $sqlQuery, // sql_query
            null // complete_query
        );
    }
}
