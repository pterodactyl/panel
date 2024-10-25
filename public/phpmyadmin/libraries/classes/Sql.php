<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Display\Results as DisplayResults;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function array_keys;
use function array_map;
use function bin2hex;
use function ceil;
use function count;
use function defined;
use function explode;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function is_bool;
use function is_object;
use function session_start;
use function session_write_close;
use function sprintf;
use function str_contains;
use function str_replace;
use function ucwords;

/**
 * Set of functions for the SQL executor
 */
class Sql
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Relation */
    private $relation;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var Transformations */
    private $transformations;

    /** @var Operations */
    private $operations;

    /** @var Template */
    private $template;

    public function __construct(
        DatabaseInterface $dbi,
        Relation $relation,
        RelationCleanup $relationCleanup,
        Operations $operations,
        Transformations $transformations,
        Template $template
    ) {
        $this->dbi = $dbi;
        $this->relation = $relation;
        $this->relationCleanup = $relationCleanup;
        $this->operations = $operations;
        $this->transformations = $transformations;
        $this->template = $template;
    }

    /**
     * Handle remembered sorting order, only for single table query
     *
     * @param string $db                 database name
     * @param string $table              table name
     * @param array  $analyzedSqlResults the analyzed query results
     * @param string $fullSqlQuery       SQL query
     */
    private function handleSortOrder(
        $db,
        $table,
        array &$analyzedSqlResults,
        &$fullSqlQuery
    ): void {
        $tableObject = new Table($table, $db);

        if (empty($analyzedSqlResults['order'])) {
            // Retrieving the name of the column we should sort after.
            $sortCol = $tableObject->getUiProp(Table::PROP_SORTED_COLUMN);
            if (empty($sortCol)) {
                return;
            }

            // Remove the name of the table from the retrieved field name.
            $sortCol = str_replace(
                Util::backquote($table) . '.',
                '',
                $sortCol
            );

            // Create the new query.
            $fullSqlQuery = Query::replaceClause(
                $analyzedSqlResults['statement'],
                $analyzedSqlResults['parser']->list,
                'ORDER BY ' . $sortCol
            );

            // TODO: Avoid reparsing the query.
            $analyzedSqlResults = Query::getAll($fullSqlQuery);
        } else {
            // Store the remembered table into session.
            $tableObject->setUiProp(
                Table::PROP_SORTED_COLUMN,
                Query::getClause(
                    $analyzedSqlResults['statement'],
                    $analyzedSqlResults['parser']->list,
                    'ORDER BY'
                )
            );
        }
    }

    /**
     * Append limit clause to SQL query
     *
     * @param array $analyzedSqlResults the analyzed query results
     *
     * @return string limit clause appended SQL query
     */
    private function getSqlWithLimitClause(array $analyzedSqlResults)
    {
        return Query::replaceClause(
            $analyzedSqlResults['statement'],
            $analyzedSqlResults['parser']->list,
            'LIMIT ' . $_SESSION['tmpval']['pos'] . ', '
            . $_SESSION['tmpval']['max_rows']
        );
    }

    /**
     * Verify whether the result set has columns from just one table
     *
     * @param array $fieldsMeta meta fields
     */
    private function resultSetHasJustOneTable(array $fieldsMeta): bool
    {
        $justOneTable = true;
        $prevTable = '';
        foreach ($fieldsMeta as $oneFieldMeta) {
            if ($oneFieldMeta->table != '' && $prevTable != '' && $oneFieldMeta->table != $prevTable) {
                $justOneTable = false;
            }

            if ($oneFieldMeta->table == '') {
                continue;
            }

            $prevTable = $oneFieldMeta->table;
        }

        return $justOneTable && $prevTable != '';
    }

    /**
     * Verify whether the result set contains all the columns
     * of at least one unique key
     *
     * @param string $db         database name
     * @param string $table      table name
     * @param array  $fieldsMeta meta fields
     */
    private function resultSetContainsUniqueKey(string $db, string $table, array $fieldsMeta): bool
    {
        $columns = $this->dbi->getColumns($db, $table);
        $resultSetColumnNames = [];
        foreach ($fieldsMeta as $oneMeta) {
            $resultSetColumnNames[] = $oneMeta->name;
        }

        foreach (Index::getFromTable($table, $db) as $index) {
            if (! $index->isUnique()) {
                continue;
            }

            $indexColumns = $index->getColumns();
            $numberFound = 0;
            foreach (array_keys($indexColumns) as $indexColumnName) {
                if (
                    ! in_array($indexColumnName, $resultSetColumnNames)
                    && in_array($indexColumnName, $columns)
                    && ! str_contains($columns[$indexColumnName]['Extra'], 'INVISIBLE')
                ) {
                    continue;
                }

                $numberFound++;
            }

            if ($numberFound == count($indexColumns)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the HTML for relational column dropdown
     * During grid edit, if we have a relational field, returns the html for the
     * dropdown
     *
     * @param string $db           current database
     * @param string $table        current table
     * @param string $column       current column
     * @param string $currentValue current selected value
     *
     * @return string html for the dropdown
     */
    public function getHtmlForRelationalColumnDropdown($db, $table, $column, $currentValue)
    {
        $foreigners = $this->relation->getForeigners($db, $table, $column);

        $foreignData = $this->relation->getForeignData($foreigners, $column, false, '', '');

        if ($foreignData['disp_row'] == null) {
            //Handle the case when number of values
            //is more than $cfg['ForeignKeyMaxLimit']
            $urlParams = [
                'db' => $db,
                'table' => $table,
                'field' => $column,
            ];

            $dropdown = $this->template->render('sql/relational_column_dropdown', [
                'current_value' => $_POST['curr_value'],
                'params' => $urlParams,
            ]);
        } else {
            $dropdown = $this->relation->foreignDropdown(
                $foreignData['disp_row'],
                $foreignData['foreign_field'],
                $foreignData['foreign_display'],
                $currentValue,
                $GLOBALS['cfg']['ForeignKeyMaxLimit']
            );
            $dropdown = '<select>' . $dropdown . '</select>';
        }

        return $dropdown;
    }

    /** @return array<string, int|array> */
    private function getDetailedProfilingStats(array $profilingResults): array
    {
        $profiling = [
            'total_time' => 0,
            'states' => [],
            'chart' => [],
            'profile' => [],
        ];

        foreach ($profilingResults as $oneResult) {
            $status = ucwords($oneResult['Status']);
            $profiling['total_time'] += $oneResult['Duration'];
            $profiling['profile'][] = [
                'status' => $status,
                'duration' => Util::formatNumber($oneResult['Duration'], 3, 1),
                'duration_raw' => $oneResult['Duration'],
            ];

            if (! isset($profiling['states'][$status])) {
                $profiling['states'][$status] = [
                    'total_time' => $oneResult['Duration'],
                    'calls' => 1,
                ];
                $profiling['chart'][$status] = $oneResult['Duration'];
            } else {
                $profiling['states'][$status]['calls']++;
                $profiling['chart'][$status] += $oneResult['Duration'];
            }
        }

        return $profiling;
    }

    /**
     * Get value of a column for a specific row (marked by $whereClause)
     */
    public function getFullValuesForSetColumn(
        string $db,
        string $table,
        string $column,
        string $whereClause
    ): string {
        $row = $this->dbi->fetchSingleRow(sprintf(
            'SELECT `%s` FROM `%s`.`%s` WHERE %s',
            $column,
            $db,
            $table,
            $whereClause
        ));

        if ($row === null) {
            return '';
        }

        return $row[$column];
    }

    /**
     * Get all the values for a enum column or set column in a table
     *
     * @param string $db     current database
     * @param string $table  current table
     * @param string $column current column
     *
     * @return array|null array containing the value list for the column, null on failure
     */
    public function getValuesForColumn(string $db, string $table, string $column): ?array
    {
        $fieldInfoQuery = QueryGenerator::getColumnsSql($db, $table, $this->dbi->escapeString($column));

        $fieldInfoResult = $this->dbi->fetchResult($fieldInfoQuery);

        if (! isset($fieldInfoResult[0])) {
            return null;
        }

        return Util::parseEnumSetValues($fieldInfoResult[0]['Type']);
    }

    /**
     * Function to check whether to remember the sorting order or not
     *
     * @param array $analyzedSqlResults the analyzed query and other variables set
     *                                    after analyzing the query
     */
    private function isRememberSortingOrder(array $analyzedSqlResults): bool
    {
        return isset($analyzedSqlResults['select_expr'], $analyzedSqlResults['select_tables'])
            && $GLOBALS['cfg']['RememberSorting']
            && ! ($analyzedSqlResults['is_count']
                || $analyzedSqlResults['is_export']
                || $analyzedSqlResults['is_func']
                || $analyzedSqlResults['is_analyse'])
            && $analyzedSqlResults['select_from']
            && (empty($analyzedSqlResults['select_expr'])
                || ((count($analyzedSqlResults['select_expr']) === 1)
                    && ($analyzedSqlResults['select_expr'][0] === '*')))
            && count($analyzedSqlResults['select_tables']) === 1;
    }

    /**
     * Function to check whether the LIMIT clause should be appended or not
     *
     * @param array $analyzedSqlResults the analyzed query and other variables set
     *                                    after analyzing the query
     */
    private function isAppendLimitClause(array $analyzedSqlResults): bool
    {
        // Assigning LIMIT clause to an syntactically-wrong query
        // is not needed. Also we would want to show the true query
        // and the true error message to the query executor

        return (isset($analyzedSqlResults['parser'])
            && count($analyzedSqlResults['parser']->errors) === 0)
            && ($_SESSION['tmpval']['max_rows'] !== 'all')
            && ! ($analyzedSqlResults['is_export']
            || $analyzedSqlResults['is_analyse'])
            && ($analyzedSqlResults['select_from']
                || $analyzedSqlResults['is_subquery'])
            && empty($analyzedSqlResults['limit']);
    }

    /**
     * Function to check whether this query is for just browsing
     *
     * @param array<string, mixed> $analyzedSqlResults the analyzed query and other variables set
     *                                                   after analyzing the query
     * @param bool|null            $findRealEnd        whether the real end should be found
     */
    public static function isJustBrowsing(array $analyzedSqlResults, ?bool $findRealEnd): bool
    {
        return ! $analyzedSqlResults['is_group']
            && ! $analyzedSqlResults['is_func']
            && empty($analyzedSqlResults['union'])
            && empty($analyzedSqlResults['distinct'])
            && $analyzedSqlResults['select_from']
            && (count($analyzedSqlResults['select_tables']) === 1)
            && (empty($analyzedSqlResults['statement']->where)
                || (count($analyzedSqlResults['statement']->where) === 1
                    && $analyzedSqlResults['statement']->where[0]->expr === '1'))
            && empty($analyzedSqlResults['group'])
            && ! isset($findRealEnd)
            && ! $analyzedSqlResults['is_subquery']
            && ! $analyzedSqlResults['join']
            && empty($analyzedSqlResults['having']);
    }

    /**
     * Function to check whether the related transformation information should be deleted
     *
     * @param array $analyzedSqlResults the analyzed query and other variables set
     *                                    after analyzing the query
     */
    private function isDeleteTransformationInfo(array $analyzedSqlResults): bool
    {
        return ! empty($analyzedSqlResults['querytype'])
            && (($analyzedSqlResults['querytype'] === 'ALTER')
                || ($analyzedSqlResults['querytype'] === 'DROP'));
    }

    /**
     * Function to check whether the user has rights to drop the database
     *
     * @param array $analyzedSqlResults    the analyzed query and other variables set
     *                                       after analyzing the query
     * @param bool  $allowUserDropDatabase whether the user is allowed to drop db
     * @param bool  $isSuperUser           whether this user is a superuser
     */
    public function hasNoRightsToDropDatabase(
        array $analyzedSqlResults,
        $allowUserDropDatabase,
        $isSuperUser
    ): bool {
        return ! $allowUserDropDatabase
            && isset($analyzedSqlResults['drop_database'])
            && $analyzedSqlResults['drop_database']
            && ! $isSuperUser;
    }

    /**
     * Function to set a column property
     *
     * @param Table  $table        Table instance
     * @param string $requestIndex col_order|col_visib
     *
     * @return bool|Message
     */
    public function setColumnProperty(Table $table, string $requestIndex)
    {
        $propertyValue = array_map('intval', explode(',', $_POST[$requestIndex]));
        switch ($requestIndex) {
            case 'col_order':
                $propertyToSet = Table::PROP_COLUMN_ORDER;
                break;
            case 'col_visib':
                $propertyToSet = Table::PROP_COLUMN_VISIB;
                break;
            default:
                $propertyToSet = '';
        }

        return $table->setUiProp($propertyToSet, $propertyValue, $_POST['table_create_time'] ?? null);
    }

    /**
     * Function to find the real end of rows
     *
     * @param string $db    the current database
     * @param string $table the current table
     *
     * @return mixed the number of rows if "retain" param is true, otherwise true
     */
    public function findRealEndOfRows($db, $table)
    {
        $unlimNumRows = $this->dbi->getTable($db, $table)->countRecords(true);
        $_SESSION['tmpval']['pos'] = $this->getStartPosToDisplayRow($unlimNumRows);

        return $unlimNumRows;
    }

    /**
     * Function to get the default sql query for browsing page
     *
     * @param string $db    the current database
     * @param string $table the current table
     *
     * @return string the default $sql_query for browse page
     */
    public function getDefaultSqlQueryForBrowse($db, $table): string
    {
        $bookmark = Bookmark::get($this->dbi, $GLOBALS['cfg']['Server']['user'], $db, $table, 'label', false, true);

        if ($bookmark !== null && $bookmark->getQuery() !== '') {
            $GLOBALS['using_bookmark_message'] = Message::notice(
                __('Using bookmark "%s" as default browse query.')
            );
            $GLOBALS['using_bookmark_message']->addParam($table);
            $GLOBALS['using_bookmark_message']->addHtml(
                MySQLDocumentation::showDocumentation('faq', 'faq6-22')
            );

            return $bookmark->getQuery();
        }

        $defaultOrderByClause = '';

        if (
            isset($GLOBALS['cfg']['TablePrimaryKeyOrder'])
            && ($GLOBALS['cfg']['TablePrimaryKeyOrder'] !== 'NONE')
        ) {
            $primaryKey = null;
            $primary = Index::getPrimary($table, $db);

            if ($primary !== false) {
                $primarycols = $primary->getColumns();

                foreach ($primarycols as $col) {
                    $primaryKey = $col->getName();
                    break;
                }

                if ($primaryKey !== null) {
                    $defaultOrderByClause = ' ORDER BY '
                        . Util::backquote($table) . '.'
                        . Util::backquote($primaryKey) . ' '
                        . $GLOBALS['cfg']['TablePrimaryKeyOrder'];
                }
            }
        }

        return 'SELECT * FROM ' . Util::backquote($table) . $defaultOrderByClause;
    }

    /**
     * Responds an error when an error happens when executing the query
     *
     * @param bool   $isGotoFile   whether goto file or not
     * @param string $error        error after executing the query
     * @param string $fullSqlQuery full sql query
     */
    private function handleQueryExecuteError($isGotoFile, $error, $fullSqlQuery): void
    {
        if ($isGotoFile) {
            $message = Message::rawError($error);
            $response = ResponseRenderer::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $message);
        } else {
            Generator::mysqlDie($error, $fullSqlQuery, false);
        }

        exit;
    }

    /**
     * Function to store the query as a bookmark
     *
     * @param string $db                  the current database
     * @param string $bookmarkUser        the bookmarking user
     * @param string $sqlQueryForBookmark the query to be stored in bookmark
     * @param string $bookmarkLabel       bookmark label
     * @param bool   $bookmarkReplace     whether to replace existing bookmarks
     */
    public function storeTheQueryAsBookmark(
        ?BookmarkFeature $bookmarkFeature,
        $db,
        $bookmarkUser,
        $sqlQueryForBookmark,
        $bookmarkLabel,
        bool $bookmarkReplace
    ): void {
        $bfields = [
            'bkm_database' => $db,
            'bkm_user' => $bookmarkUser,
            'bkm_sql_query' => $sqlQueryForBookmark,
            'bkm_label' => $bookmarkLabel,
        ];

        // Should we replace bookmark?
        if ($bookmarkReplace && $bookmarkFeature !== null) {
            $bookmarks = Bookmark::getList($bookmarkFeature, $this->dbi, $GLOBALS['cfg']['Server']['user'], $db);
            foreach ($bookmarks as $bookmark) {
                if ($bookmark->getLabel() != $bookmarkLabel) {
                    continue;
                }

                $bookmark->delete();
            }
        }

        $bookmark = Bookmark::createBookmark(
            $this->dbi,
            $bfields,
            isset($_POST['bkm_all_users'])
        );

        if ($bookmark === false) {
            return;
        }

        $bookmark->save();
    }

    /**
     * Function to get the affected or changed number of rows after executing a query
     *
     * @param bool                  $isAffected whether the query affected a table
     * @param ResultInterface|false $result     results of executing the query
     *
     * @return int|string number of rows affected or changed
     * @psalm-return int|numeric-string
     */
    private function getNumberOfRowsAffectedOrChanged($isAffected, $result)
    {
        if ($isAffected) {
            return $this->dbi->affectedRows();
        }

        if ($result) {
            return $result->numRows();
        }

        return 0;
    }

    /**
     * Checks if the current database has changed
     * This could happen if the user sends a query like "USE `database`;"
     *
     * @param string $db the database in the query
     *
     * @return bool whether to reload the navigation(1) or not(0)
     */
    private function hasCurrentDbChanged(string $db): bool
    {
        if ($db === '') {
            return false;
        }

        $currentDb = $this->dbi->fetchValue('SELECT DATABASE()');

        // $current_db is false, except when a USE statement was sent
        return ($currentDb != false) && ($db !== $currentDb);
    }

    /**
     * If a table, database or column gets dropped, clean comments.
     *
     * @param string      $db     current database
     * @param string      $table  current table
     * @param string|null $column current column
     * @param bool        $purge  whether purge set or not
     */
    private function cleanupRelations(string $db, string $table, ?string $column, bool $purge): void
    {
        if (! $purge || $db === '') {
            return;
        }

        if ($table !== '') {
            if ($column !== null && $column !== '') {
                $this->relationCleanup->column($db, $table, $column);
            } else {
                $this->relationCleanup->table($db, $table);
            }
        } else {
            $this->relationCleanup->database($db);
        }
    }

    /**
     * Function to count the total number of rows for the same 'SELECT' query without
     * the 'LIMIT' clause that may have been programmatically added
     *
     * @param int|string $numRows            number of rows affected/changed by the query
     * @param bool       $justBrowsing       whether just browsing or not
     * @param string     $db                 the current database
     * @param string     $table              the current table
     * @param array      $analyzedSqlResults the analyzed query and other variables set after analyzing the query
     * @psalm-param int|numeric-string $numRows
     *
     * @return int|string unlimited number of rows
     * @psalm-return int|numeric-string
     */
    private function countQueryResults(
        $numRows,
        bool $justBrowsing,
        string $db,
        string $table,
        array $analyzedSqlResults
    ) {
        /* Shortcut for not analyzed/empty query */
        if ($analyzedSqlResults === []) {
            return 0;
        }

        if (! $this->isAppendLimitClause($analyzedSqlResults)) {
            // if we did not append a limit, set this to get a correct
            // "Showing rows..." message
            // $_SESSION['tmpval']['max_rows'] = 'all';
            $unlimNumRows = $numRows;
        } elseif ($_SESSION['tmpval']['max_rows'] > $numRows) {
            // When user has not defined a limit in query and total rows in
            // result are less than max_rows to display, there is no need
            // to count total rows for that query again
            $unlimNumRows = $_SESSION['tmpval']['pos'] + $numRows;
        } elseif ($analyzedSqlResults['querytype'] === 'SELECT' || $analyzedSqlResults['is_subquery']) {
            //    c o u n t    q u e r y

            // If we are "just browsing", there is only one table (and no join),
            // and no WHERE clause (or just 'WHERE 1 '),
            // we do a quick count (which uses MaxExactCount) because
            // SQL_CALC_FOUND_ROWS is not quick on large InnoDB tables

            // However, do not count again if we did it previously
            // due to $find_real_end == true
            if ($justBrowsing) {
                // Get row count (is approximate for InnoDB)
                $unlimNumRows = $this->dbi->getTable($db, $table)->countRecords();
                /**
                 * @todo Can we know at this point that this is InnoDB,
                 *       (in this case there would be no need for getting
                 *       an exact count)?
                 */
                if ($unlimNumRows < $GLOBALS['cfg']['MaxExactCount']) {
                    // Get the exact count if approximate count
                    // is less than MaxExactCount
                    /**
                     * @todo In countRecords(), MaxExactCount is also verified,
                     *       so can we avoid checking it twice?
                     */
                    $unlimNumRows = $this->dbi->getTable($db, $table)
                        ->countRecords(true);
                }
            } else {
                $statement = $analyzedSqlResults['statement'];
                $tokenList = $analyzedSqlResults['parser']->list;
                $replaces = [
                    // Remove ORDER BY to decrease unnecessary sorting time
                    [
                        'ORDER BY',
                        '',
                    ],
                    // Removes LIMIT clause that might have been added
                    [
                        'LIMIT',
                        '',
                    ],
                ];
                $countQuery = 'SELECT COUNT(*) FROM (' . Query::replaceClauses(
                    $statement,
                    $tokenList,
                    $replaces
                ) . ') as cnt';
                $unlimNumRows = $this->dbi->fetchValue($countQuery);
                if ($unlimNumRows === false) {
                    $unlimNumRows = 0;
                }
            }
        } else {// not $is_select
            $unlimNumRows = 0;
        }

        return $unlimNumRows;
    }

    /**
     * Function to handle all aspects relating to executing the query
     *
     * @param array       $analyzedSqlResults  analyzed sql results
     * @param string      $fullSqlQuery        full sql query
     * @param bool        $isGotoFile          whether to go to a file
     * @param string      $db                  current database
     * @param string|null $table               current table
     * @param bool|null   $findRealEnd         whether to find the real end
     * @param string|null $sqlQueryForBookmark sql query to be stored as bookmark
     * @param array|null  $extraData           extra data
     *
     * @psalm-return array{
     *  ResultInterface|false|null,
     *  int|numeric-string,
     *  int|numeric-string,
     *  array<string, string>|null,
     *  array|null
     * }
     */
    private function executeTheQuery(
        array $analyzedSqlResults,
        $fullSqlQuery,
        $isGotoFile,
        string $db,
        ?string $table,
        ?bool $findRealEnd,
        ?string $sqlQueryForBookmark,
        $extraData
    ): array {
        $response = ResponseRenderer::getInstance();
        $response->getHeader()->getMenu()->setTable($table ?? '');

        // Only if we ask to see the php code
        if (isset($GLOBALS['show_as_php'])) {
            $result = null;
            $numRows = 0;
            $unlimNumRows = 0;
            $profilingResults = null;
        } else { // If we don't ask to see the php code
            Profiling::enable($this->dbi);

            if (! defined('TESTSUITE')) {
                // close session in case the query takes too long
                session_write_close();
            }

            $result = $this->dbi->tryQuery($fullSqlQuery);
            $GLOBALS['querytime'] = $this->dbi->lastQueryExecutionTime;

            if (! defined('TESTSUITE')) {
                // reopen session
                session_start();
            }

            // Displays an error message if required and stop parsing the script
            $error = $this->dbi->getError();
            if ($error && $GLOBALS['cfg']['IgnoreMultiSubmitErrors']) {
                $extraData['error'] = $error;
            } elseif ($error) {
                $this->handleQueryExecuteError($isGotoFile, $error, $fullSqlQuery);
            }

            // If there are no errors and bookmarklabel was given,
            // store the query as a bookmark
            if (! empty($_POST['bkm_label']) && $sqlQueryForBookmark) {
                $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
                $this->storeTheQueryAsBookmark(
                    $bookmarkFeature,
                    $db,
                    $bookmarkFeature !== null ? $GLOBALS['cfg']['Server']['user'] : '',
                    $sqlQueryForBookmark,
                    $_POST['bkm_label'],
                    isset($_POST['bkm_replace'])
                );
            }

            // Gets the number of rows affected/returned
            // (This must be done immediately after the query because
            // mysql_affected_rows() reports about the last query done)
            $numRows = $this->getNumberOfRowsAffectedOrChanged($analyzedSqlResults['is_affected'], $result);

            $profilingResults = Profiling::getInformation($this->dbi);

            $justBrowsing = self::isJustBrowsing($analyzedSqlResults, $findRealEnd ?? null);

            $unlimNumRows = $this->countQueryResults($numRows, $justBrowsing, $db, $table ?? '', $analyzedSqlResults);

            $this->cleanupRelations($db, $table ?? '', $_POST['dropped_column'] ?? null, ! empty($_POST['purge']));

            if (
                isset($_POST['dropped_column'])
                && $db !== '' && $table !== null && $table !== ''
            ) {
                // to refresh the list of indexes (Ajax mode)

                $indexes = Index::getFromTable($table, $db);
                $indexesDuplicates = Index::findDuplicates($table, $db);
                $template = new Template();

                $extraData['indexes_list'] = $template->render('indexes', [
                    'url_params' => $GLOBALS['urlParams'],
                    'indexes' => $indexes,
                    'indexes_duplicates' => $indexesDuplicates,
                ]);
            }
        }

        return [
            $result,
            $numRows,
            $unlimNumRows,
            $profilingResults,
            $extraData,
        ];
    }

    /**
     * Delete related transformation information
     *
     * @param string $db                 current database
     * @param string $table              current table
     * @param array  $analyzedSqlResults analyzed sql results
     */
    private function deleteTransformationInfo(string $db, string $table, array $analyzedSqlResults): void
    {
        if (! isset($analyzedSqlResults['statement'])) {
            return;
        }

        $statement = $analyzedSqlResults['statement'];
        if ($statement instanceof AlterStatement) {
            if (
                ! empty($statement->altered[0])
                && $statement->altered[0]->options->has('DROP')
                && ! empty($statement->altered[0]->field->column)
            ) {
                $this->transformations->clear($db, $table, $statement->altered[0]->field->column);
            }
        } elseif ($statement instanceof DropStatement) {
            $this->transformations->clear($db, $table);
        }
    }

    /**
     * Function to get the message for the no rows returned case
     *
     * @param string|null $messageToShow      message to show
     * @param array       $analyzedSqlResults analyzed sql results
     * @param int|string  $numRows            number of rows
     */
    private function getMessageForNoRowsReturned(
        ?string $messageToShow,
        array $analyzedSqlResults,
        $numRows
    ): Message {
        if ($analyzedSqlResults['querytype'] === 'DELETE') {
            $message = Message::getMessageForDeletedRows($numRows);
        } elseif ($analyzedSqlResults['is_insert']) {
            if ($analyzedSqlResults['querytype'] === 'REPLACE') {
                // For REPLACE we get DELETED + INSERTED row count,
                // so we have to call it affected
                $message = Message::getMessageForAffectedRows($numRows);
            } else {
                $message = Message::getMessageForInsertedRows($numRows);
            }

            $insertId = $this->dbi->insertId();
            if ($insertId !== 0) {
                // insert_id is id of FIRST record inserted in one insert,
                // so if we inserted multiple rows, we had to increment this
                $message->addText('[br]');
                // need to use a temporary because the Message class
                // currently supports adding parameters only to the first
                // message
                $inserted = Message::notice(__('Inserted row id: %1$d'));
                $inserted->addParam($insertId + $numRows - 1);
                $message->addMessage($inserted);
            }
        } elseif ($analyzedSqlResults['is_affected']) {
            $message = Message::getMessageForAffectedRows($numRows);

            // Ok, here is an explanation for the !$is_select.
            // The form generated by PhpMyAdmin\SqlQueryForm
            // and /database/sql has many submit buttons
            // on the same form, and some confusion arises from the
            // fact that $message_to_show is sent for every case.
            // The $message_to_show containing a success message and sent with
            // the form should not have priority over errors
        } elseif ($messageToShow && $analyzedSqlResults['querytype'] !== 'SELECT') {
            $message = Message::rawSuccess(htmlspecialchars($messageToShow));
        } elseif (! empty($GLOBALS['show_as_php'])) {
            $message = Message::success(__('Showing as PHP code'));
        } elseif (isset($GLOBALS['show_as_php'])) {
            /* User disable showing as PHP, query is only displayed */
            $message = Message::notice(__('Showing SQL query'));
        } else {
            $message = Message::success(
                __('MySQL returned an empty result set (i.e. zero rows).')
            );
        }

        if (isset($GLOBALS['querytime'])) {
            $queryTime = Message::notice(
                '(' . __('Query took %01.4f seconds.') . ')'
            );
            $queryTime->addParam($GLOBALS['querytime']);
            $message->addMessage($queryTime);
        }

        // In case of ROLLBACK, notify the user.
        if (isset($_POST['rollback_query'])) {
            $message->addText(__('[ROLLBACK occurred.]'));
        }

        return $message;
    }

    /**
     * Function to respond back when the query returns zero rows
     * This method is called
     * 1-> When browsing an empty table
     * 2-> When executing a query on a non empty table which returns zero results
     * 3-> When executing a query on an empty table
     * 4-> When executing an INSERT, UPDATE, DELETE query from the SQL tab
     * 5-> When deleting a row from BROWSE tab
     * 6-> When searching using the SEARCH tab which returns zero results
     * 7-> When changing the structure of the table except change operation
     *
     * @param array                      $analyzedSqlResults   analyzed sql results
     * @param string                     $db                   current database
     * @param string|null                $table                current table
     * @param string|null                $messageToShow        message to show
     * @param int|string                 $numRows              number of rows
     * @param DisplayResults             $displayResultsObject DisplayResult instance
     * @param array|null                 $extraData            extra data
     * @param array|null                 $profilingResults     profiling results
     * @param ResultInterface|false|null $result               executed query results
     * @param string                     $sqlQuery             sql query
     * @param string|null                $completeQuery        complete sql query
     * @psalm-param int|numeric-string $numRows
     *
     * @return string html
     */
    private function getQueryResponseForNoResultsReturned(
        array $analyzedSqlResults,
        string $db,
        ?string $table,
        ?string $messageToShow,
        $numRows,
        $displayResultsObject,
        ?array $extraData,
        ?array $profilingResults,
        $result,
        $sqlQuery,
        ?string $completeQuery
    ): string {
        if ($this->isDeleteTransformationInfo($analyzedSqlResults)) {
            $this->deleteTransformationInfo($db, $table ?? '', $analyzedSqlResults);
        }

        if (isset($extraData['error'])) {
            $message = Message::rawError($extraData['error']);
        } else {
            $message = $this->getMessageForNoRowsReturned($messageToShow, $analyzedSqlResults, $numRows);
        }

        $queryMessage = Generator::getMessage($message, $GLOBALS['sql_query'], 'success');

        if (isset($GLOBALS['show_as_php'])) {
            return $queryMessage;
        }

        if (! empty($GLOBALS['reload'])) {
            $extraData['reload'] = 1;
            $extraData['db'] = $GLOBALS['db'];
        }

        // For ajax requests add message and sql_query as JSON
        if (empty($_REQUEST['ajax_page_request'])) {
            $extraData['message'] = $message;
            if ($GLOBALS['cfg']['ShowSQL']) {
                $extraData['sql_query'] = $queryMessage;
            }
        }

        $response = ResponseRenderer::getInstance();
        $response->addJSON($extraData ?? []);

        if (empty($analyzedSqlResults['is_select']) || isset($extraData['error'])) {
            return $queryMessage;
        }

        $displayParts = [
            'edit_lnk' => null,
            'del_lnk' => null,
            'sort_lnk' => '1',
            'nav_bar' => '0',
            'bkm_form' => '1',
            'text_btn' => '1',
            'pview_lnk' => '1',
        ];

        $sqlQueryResultsTable = $this->getHtmlForSqlQueryResultsTable(
            $displayResultsObject,
            $displayParts,
            false,
            0,
            $numRows,
            null,
            $result,
            $analyzedSqlResults,
            true
        );

        $profilingChart = '';
        if ($profilingResults !== null) {
            $header = $response->getHeader();
            $scripts = $header->getScripts();
            $scripts->addFile('sql.js');

            $profiling = $this->getDetailedProfilingStats($profilingResults);
            $profilingChart = $this->template->render('sql/profiling_chart', ['profiling' => $profiling]);
        }

        $bookmark = '';
        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        if (
            $bookmarkFeature !== null
            && empty($_GET['id_bookmark'])
            && $sqlQuery
        ) {
            $bookmark = $this->template->render('sql/bookmark', [
                'db' => $db,
                'goto' => Url::getFromRoute('/sql', [
                    'db' => $db,
                    'table' => $table,
                    'sql_query' => $sqlQuery,
                    'id_bookmark' => 1,
                ]),
                'user' => $GLOBALS['cfg']['Server']['user'],
                'sql_query' => $completeQuery ?? $sqlQuery,
            ]);
        }

        return $this->template->render('sql/no_results_returned', [
            'message' => $queryMessage,
            'sql_query_results_table' => $sqlQueryResultsTable,
            'profiling_chart' => $profilingChart,
            'bookmark' => $bookmark,
            'db' => $db,
            'table' => $table,
            'sql_query' => $sqlQuery,
            'is_procedure' => ! empty($analyzedSqlResults['procedure']),
        ]);
    }

    /**
     * Function to send response for ajax grid edit
     *
     * @param ResultInterface $result result of the executed query
     */
    private function getResponseForGridEdit(ResultInterface $result): void
    {
        $row = $result->fetchRow();
        $fieldsMeta = $this->dbi->getFieldsMeta($result);

        if (isset($fieldsMeta[0]) && $fieldsMeta[0]->isBinary()) {
            $row[0] = bin2hex($row[0]);
        }

        $response = ResponseRenderer::getInstance();
        $response->addJSON('value', $row[0]);
    }

    /**
     * Returns a message for successful creation of a bookmark or null if a bookmark
     * was not created
     */
    private function getBookmarkCreatedMessage(): string
    {
        $output = '';
        if (isset($_GET['label'])) {
            $message = Message::success(
                __('Bookmark %s has been created.')
            );
            $message->addParam($_GET['label']);
            $output = $message->getDisplay();
        }

        return $output;
    }

    /**
     * Function to get html for the sql query results table
     *
     * @param DisplayResults             $displayResultsObject instance of DisplayResult
     * @param array                      $displayParts         the parts to display
     * @param bool                       $editable             whether the result table is
     *                                                         editable or not
     * @param int|string                 $unlimNumRows         unlimited number of rows
     * @param int|string                 $numRows              number of rows
     * @param array|null                 $showTable            table definitions
     * @param ResultInterface|false|null $result               result of the executed query
     * @param array                      $analyzedSqlResults   analyzed sql results
     * @param bool                       $isLimitedDisplay     Show only limited operations or not
     * @psalm-param int|numeric-string $unlimNumRows
     * @psalm-param int|numeric-string $numRows
     */
    private function getHtmlForSqlQueryResultsTable(
        $displayResultsObject,
        array $displayParts,
        $editable,
        $unlimNumRows,
        $numRows,
        ?array $showTable,
        $result,
        array $analyzedSqlResults,
        $isLimitedDisplay = false
    ): string {
        $printView = isset($_POST['printview']) && $_POST['printview'] == '1' ? '1' : null;
        $tableHtml = '';
        $isBrowseDistinct = ! empty($_POST['is_browse_distinct']);

        if ($analyzedSqlResults['is_procedure']) {
            do {
                if ($result === null) {
                    $result = $this->dbi->storeResult();
                }

                if ($result === false) {
                    $result = null;
                    continue;
                }

                $numRows = $result->numRows();

                if ($numRows > 0) {
                    $fieldsMeta = $this->dbi->getFieldsMeta($result);
                    $fieldsCount = count($fieldsMeta);

                    $displayResultsObject->setProperties(
                        $numRows,
                        $fieldsMeta,
                        $analyzedSqlResults['is_count'],
                        $analyzedSqlResults['is_export'],
                        $analyzedSqlResults['is_func'],
                        $analyzedSqlResults['is_analyse'],
                        $numRows,
                        $fieldsCount,
                        $GLOBALS['querytime'],
                        $GLOBALS['text_dir'],
                        $analyzedSqlResults['is_maint'],
                        $analyzedSqlResults['is_explain'],
                        $analyzedSqlResults['is_show'],
                        $showTable,
                        $printView,
                        $editable,
                        $isBrowseDistinct
                    );

                    $displayParts = [
                        'edit_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                        'del_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                        'sort_lnk' => '1',
                        'nav_bar' => '1',
                        'bkm_form' => '1',
                        'text_btn' => '1',
                        'pview_lnk' => '1',
                    ];

                    $tableHtml .= $displayResultsObject->getTable(
                        $result,
                        $displayParts,
                        $analyzedSqlResults,
                        $isLimitedDisplay
                    );
                }

                $result = null;
            } while ($this->dbi->moreResults() && $this->dbi->nextResult());
        } else {
            $fieldsMeta = [];
            if (isset($result) && ! is_bool($result)) {
                $fieldsMeta = $this->dbi->getFieldsMeta($result);
            }

            $fieldsCount = count($fieldsMeta);
            $_SESSION['is_multi_query'] = false;
            $displayResultsObject->setProperties(
                $unlimNumRows,
                $fieldsMeta,
                $analyzedSqlResults['is_count'],
                $analyzedSqlResults['is_export'],
                $analyzedSqlResults['is_func'],
                $analyzedSqlResults['is_analyse'],
                $numRows,
                $fieldsCount,
                $GLOBALS['querytime'],
                $GLOBALS['text_dir'],
                $analyzedSqlResults['is_maint'],
                $analyzedSqlResults['is_explain'],
                $analyzedSqlResults['is_show'],
                $showTable,
                $printView,
                $editable,
                $isBrowseDistinct
            );

            if (! is_bool($result)) {
                $tableHtml .= $displayResultsObject->getTable(
                    $result,
                    $displayParts,
                    $analyzedSqlResults,
                    $isLimitedDisplay
                );
            }
        }

        return $tableHtml;
    }

    /**
     * Function to get html for the previous query if there is such.
     *
     * @param string|null    $displayQuery   display query
     * @param bool           $showSql        whether to show sql
     * @param array          $sqlData        sql data
     * @param Message|string $displayMessage display message
     */
    private function getHtmlForPreviousUpdateQuery(
        ?string $displayQuery,
        bool $showSql,
        array $sqlData,
        $displayMessage
    ): string {
        $output = '';
        if ($displayQuery !== null && $showSql && $sqlData === []) {
            $output = Generator::getMessage($displayMessage, $displayQuery, 'success');
        }

        return $output;
    }

    /**
     * To get the message if a column index is missing. If not will return null
     *
     * @param string|null $table        current table
     * @param string      $database     current database
     * @param bool        $editable     whether the results table can be editable or not
     * @param bool        $hasUniqueKey whether there is a unique key
     */
    private function getMessageIfMissingColumnIndex(
        ?string $table,
        string $database,
        bool $editable,
        bool $hasUniqueKey
    ): string {
        if ($table === null) {
            return '';
        }

        $output = '';
        if (Utilities::isSystemSchema($database) || ! $editable) {
            $output = Message::notice(
                sprintf(
                    __(
                        'Current selection does not contain a unique column.'
                        . ' Grid edit, checkbox, Edit, Copy and Delete features'
                        . ' are not available. %s'
                    ),
                    MySQLDocumentation::showDocumentation(
                        'config',
                        'cfg_RowActionLinksWithoutUnique'
                    )
                )
            )->getDisplay();
        } elseif (! $hasUniqueKey) {
            $output = Message::notice(
                sprintf(
                    __(
                        'Current selection does not contain a unique column.'
                        . ' Grid edit, Edit, Copy and Delete features may result in'
                        . ' undesired behavior. %s'
                    ),
                    MySQLDocumentation::showDocumentation(
                        'config',
                        'cfg_RowActionLinksWithoutUnique'
                    )
                )
            )->getDisplay();
        }

        return $output;
    }

    /**
     * Function to display results when the executed query returns non empty results
     *
     * @param ResultInterface|false|null $result               executed query results
     * @param array                      $analyzedSqlResults   analysed sql results
     * @param string                     $db                   current database
     * @param string|null                $table                current table
     * @param array|null                 $sqlData              sql data
     * @param DisplayResults             $displayResultsObject Instance of DisplayResults
     * @param int|string                 $unlimNumRows         unlimited number of rows
     * @param int|string                 $numRows              number of rows
     * @param string|null                $dispQuery            display query
     * @param Message|string|null        $dispMessage          display message
     * @param array|null                 $profilingResults     profiling results
     * @param string                     $sqlQuery             sql query
     * @param string|null                $completeQuery        complete sql query
     * @psalm-param int|numeric-string $unlimNumRows
     * @psalm-param int|numeric-string $numRows
     *
     * @return string html
     */
    private function getQueryResponseForResultsReturned(
        $result,
        array $analyzedSqlResults,
        string $db,
        ?string $table,
        ?array $sqlData,
        $displayResultsObject,
        $unlimNumRows,
        $numRows,
        ?string $dispQuery,
        $dispMessage,
        ?array $profilingResults,
        $sqlQuery,
        ?string $completeQuery
    ): string {
        global $showtable;

        // If we are retrieving the full value of a truncated field or the original
        // value of a transformed field, show it here
        if (isset($_POST['grid_edit']) && $_POST['grid_edit'] == true && is_object($result)) {
            $this->getResponseForGridEdit($result);
            exit;
        }

        // Gets the list of fields properties
        $fieldsMeta = [];
        if ($result !== null && ! is_bool($result)) {
            $fieldsMeta = $this->dbi->getFieldsMeta($result);
        }

        // Should be initialized these parameters before parsing
        if (! is_array($showtable)) {
            $showtable = null;
        }

        $response = ResponseRenderer::getInstance();
        $header = $response->getHeader();
        $scripts = $header->getScripts();

        $justOneTable = $this->resultSetHasJustOneTable($fieldsMeta);

        // hide edit and delete links:
        // - for information_schema
        // - if the result set does not contain all the columns of a unique key
        //   (unless this is an updatable view)
        // - if the SELECT query contains a join or a subquery

        $updatableView = false;

        $statement = $analyzedSqlResults['statement'] ?? null;
        if ($statement instanceof SelectStatement) {
            if ($statement->expr && $statement->expr[0]->expr === '*' && $table) {
                $_table = new Table($table, $db);
                $updatableView = $_table->isUpdatableView();
            }

            if (
                $analyzedSqlResults['join']
                || $analyzedSqlResults['is_subquery']
                || count($analyzedSqlResults['select_tables']) !== 1
            ) {
                $justOneTable = false;
            }
        }

        $hasUnique = $table !== null && $this->resultSetContainsUniqueKey($db, $table, $fieldsMeta);

        $editable = ($hasUnique
            || $GLOBALS['cfg']['RowActionLinksWithoutUnique']
            || $updatableView)
            && $justOneTable
            && ! Utilities::isSystemSchema($db);

        $_SESSION['tmpval']['possible_as_geometry'] = $editable;

        $displayParts = [
            'edit_lnk' => $displayResultsObject::UPDATE_ROW,
            'del_lnk' => $displayResultsObject::DELETE_ROW,
            'sort_lnk' => '1',
            'nav_bar' => '1',
            'bkm_form' => '1',
            'text_btn' => '0',
            'pview_lnk' => '1',
        ];

        if (! $editable) {
            $displayParts = [
                'edit_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                'del_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                'sort_lnk' => '1',
                'nav_bar' => '1',
                'bkm_form' => '1',
                'text_btn' => '1',
                'pview_lnk' => '1',
            ];
        }

        if (isset($_POST['printview']) && $_POST['printview'] == '1') {
            $displayParts = [
                'edit_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                'del_lnk' => $displayResultsObject::NO_EDIT_OR_DELETE,
                'sort_lnk' => '0',
                'nav_bar' => '0',
                'bkm_form' => '0',
                'text_btn' => '0',
                'pview_lnk' => '0',
            ];
        }

        if (! isset($_POST['printview']) || $_POST['printview'] != '1') {
            $scripts->addFile('makegrid.js');
            $scripts->addFile('sql.js');
            unset($GLOBALS['message']);
            //we don't need to buffer the output in getMessage here.
            //set a global variable and check against it in the function
            $GLOBALS['buffer_message'] = false;
        }

        $previousUpdateQueryHtml = $this->getHtmlForPreviousUpdateQuery(
            $dispQuery,
            (bool) $GLOBALS['cfg']['ShowSQL'],
            $sqlData ?? [],
            $dispMessage ?? ''
        );

        $profilingChartHtml = '';
        if ($profilingResults) {
            $profiling = $this->getDetailedProfilingStats($profilingResults);
            $profilingChartHtml = $this->template->render('sql/profiling_chart', ['profiling' => $profiling]);
        }

        $missingUniqueColumnMessage = $this->getMessageIfMissingColumnIndex($table, $db, $editable, $hasUnique);

        $bookmarkCreatedMessage = $this->getBookmarkCreatedMessage();

        $tableHtml = $this->getHtmlForSqlQueryResultsTable(
            $displayResultsObject,
            $displayParts,
            $editable,
            $unlimNumRows,
            $numRows,
            $showtable,
            $result,
            $analyzedSqlResults
        );

        $bookmarkSupportHtml = '';
        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        if (
            $bookmarkFeature !== null
            && $displayParts['bkm_form'] == '1'
            && empty($_GET['id_bookmark'])
            && $sqlQuery
        ) {
            $bookmarkSupportHtml = $this->template->render('sql/bookmark', [
                'db' => $db,
                'goto' => Url::getFromRoute('/sql', [
                    'db' => $db,
                    'table' => $table,
                    'sql_query' => $sqlQuery,
                    'id_bookmark' => 1,
                ]),
                'user' => $GLOBALS['cfg']['Server']['user'],
                'sql_query' => $completeQuery ?? $sqlQuery,
            ]);
        }

        return $this->template->render('sql/sql_query_results', [
            'previous_update_query' => $previousUpdateQueryHtml,
            'profiling_chart' => $profilingChartHtml,
            'missing_unique_column_message' => $missingUniqueColumnMessage,
            'bookmark_created_message' => $bookmarkCreatedMessage,
            'table' => $tableHtml,
            'bookmark_support' => $bookmarkSupportHtml,
        ]);
    }

    /**
     * Function to execute the query and send the response
     *
     * @param array|null          $analyzedSqlResults  analysed sql results
     * @param bool                $isGotoFile          whether goto file or not
     * @param string              $db                  current database
     * @param string|null         $table               current table
     * @param bool|null           $findRealEnd         whether to find real end or not
     * @param string|null         $sqlQueryForBookmark the sql query to be stored as bookmark
     * @param array|null          $extraData           extra data
     * @param string|null         $messageToShow       message to show
     * @param array|null          $sqlData             sql data
     * @param string              $goto                goto page url
     * @param string|null         $dispQuery           display query
     * @param Message|string|null $dispMessage         display message
     * @param string              $sqlQuery            sql query
     * @param string|null         $completeQuery       complete query
     */
    public function executeQueryAndSendQueryResponse(
        $analyzedSqlResults,
        $isGotoFile,
        string $db,
        ?string $table,
        $findRealEnd,
        $sqlQueryForBookmark,
        $extraData,
        $messageToShow,
        $sqlData,
        $goto,
        $dispQuery,
        $dispMessage,
        $sqlQuery,
        $completeQuery
    ): string {
        if ($analyzedSqlResults == null) {
            // Parse and analyze the query
            [
                $analyzedSqlResults,
                $db,
                $tableFromSql,
            ] = ParseAnalyze::sqlQuery($sqlQuery, $db);

            $table = $tableFromSql ?: $table;
        }

        return $this->executeQueryAndGetQueryResponse(
            $analyzedSqlResults, // analyzed_sql_results
            $isGotoFile, // is_gotofile
            $db, // db
            $table, // table
            $findRealEnd, // find_real_end
            $sqlQueryForBookmark, // sql_query_for_bookmark
            $extraData, // extra_data
            $messageToShow, // message_to_show
            $sqlData, // sql_data
            $goto, // goto
            $dispQuery, // disp_query
            $dispMessage, // disp_message
            $sqlQuery, // sql_query
            $completeQuery // complete_query
        );
    }

    /**
     * Function to execute the query and send the response
     *
     * @param array               $analyzedSqlResults  analysed sql results
     * @param bool                $isGotoFile          whether goto file or not
     * @param string              $db                  current database
     * @param string|null         $table               current table
     * @param bool|null           $findRealEnd         whether to find real end or not
     * @param string|null         $sqlQueryForBookmark the sql query to be stored as bookmark
     * @param array|null          $extraData           extra data
     * @param string|null         $messageToShow       message to show
     * @param array|null          $sqlData             sql data
     * @param string              $goto                goto page url
     * @param string|null         $dispQuery           display query
     * @param Message|string|null $dispMessage         display message
     * @param string              $sqlQuery            sql query
     * @param string|null         $completeQuery       complete query
     *
     * @return string html
     */
    public function executeQueryAndGetQueryResponse(
        array $analyzedSqlResults,
        $isGotoFile,
        string $db,
        ?string $table,
        $findRealEnd,
        ?string $sqlQueryForBookmark,
        $extraData,
        ?string $messageToShow,
        $sqlData,
        $goto,
        ?string $dispQuery,
        $dispMessage,
        $sqlQuery,
        ?string $completeQuery
    ): string {
        // Handle disable/enable foreign key checks
        $defaultFkCheck = ForeignKey::handleDisableCheckInit();

        // Handle remembered sorting order, only for single table query.
        // Handling is not required when it's a union query
        // (the parser never sets the 'union' key to 0).
        // Handling is also not required if we came from the "Sort by key"
        // drop-down.
        if (
            $analyzedSqlResults !== []
            && $this->isRememberSortingOrder($analyzedSqlResults)
            && empty($analyzedSqlResults['union'])
            && ! isset($_POST['sort_by_key'])
        ) {
            if (! isset($_SESSION['sql_from_query_box'])) {
                $this->handleSortOrder($db, $table, $analyzedSqlResults, $sqlQuery);
            } else {
                unset($_SESSION['sql_from_query_box']);
            }
        }

        $displayResultsObject = new DisplayResults(
            $GLOBALS['dbi'],
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['server'],
            $goto,
            $sqlQuery
        );
        $displayResultsObject->setConfigParamsForDisplayTable($analyzedSqlResults);

        // assign default full_sql_query
        $fullSqlQuery = $sqlQuery;

        // Do append a "LIMIT" clause?
        if ($this->isAppendLimitClause($analyzedSqlResults)) {
            $fullSqlQuery = $this->getSqlWithLimitClause($analyzedSqlResults);
        }

        $GLOBALS['reload'] = $this->hasCurrentDbChanged($db);
        $this->dbi->selectDb($db);

        [
            $result,
            $numRows,
            $unlimNumRows,
            $profilingResults,
            $extraData,
        ] = $this->executeTheQuery(
            $analyzedSqlResults,
            $fullSqlQuery,
            $isGotoFile,
            $db,
            $table,
            $findRealEnd,
            $sqlQueryForBookmark,
            $extraData
        );

        if ($this->dbi->moreResults()) {
            $this->dbi->nextResult();
        }

        $warningMessages = $this->operations->getWarningMessagesArray();

        // No rows returned -> move back to the calling page
        if (($numRows == 0 && $unlimNumRows == 0) || $analyzedSqlResults['is_affected']) {
            $htmlOutput = $this->getQueryResponseForNoResultsReturned(
                $analyzedSqlResults,
                $db,
                $table,
                $messageToShow,
                $numRows,
                $displayResultsObject,
                $extraData,
                $profilingResults,
                $result,
                $sqlQuery,
                $completeQuery
            );
        } else {
            // At least one row is returned -> displays a table with results
            $htmlOutput = $this->getQueryResponseForResultsReturned(
                $result,
                $analyzedSqlResults,
                $db,
                $table,
                $sqlData,
                $displayResultsObject,
                $unlimNumRows,
                $numRows,
                $dispQuery,
                $dispMessage,
                $profilingResults,
                $sqlQuery,
                $completeQuery
            );
        }

        // Handle disable/enable foreign key checks
        ForeignKey::handleDisableCheckCleanup($defaultFkCheck);

        foreach ($warningMessages as $warning) {
            $message = Message::notice(Message::sanitize($warning));
            $htmlOutput .= $message->getDisplay();
        }

        return $htmlOutput;
    }

    /**
     * Function to define pos to display a row
     *
     * @param int $numberOfLine Number of the line to display
     *
     * @return int Start position to display the line
     */
    private function getStartPosToDisplayRow($numberOfLine)
    {
        $maxRows = $_SESSION['tmpval']['max_rows'];

        return @((int) ceil($numberOfLine / $maxRows) - 1) * $maxRows;
    }

    /**
     * Function to calculate new pos if pos is higher than number of rows
     * of displayed table
     *
     * @param string   $db    Database name
     * @param string   $table Table name
     * @param int|null $pos   Initial position
     *
     * @return int Number of pos to display last page
     */
    public function calculatePosForLastPage($db, $table, $pos)
    {
        if ($pos === null) {
            $pos = $_SESSION['tmpval']['pos'];
        }

        $tableObject = new Table($table, $db);
        $unlimNumRows = $tableObject->countRecords(true);
        //If position is higher than number of rows
        if ($unlimNumRows <= $pos && $pos != 0) {
            $pos = $this->getStartPosToDisplayRow($unlimNumRows);
        }

        return $pos;
    }
}
