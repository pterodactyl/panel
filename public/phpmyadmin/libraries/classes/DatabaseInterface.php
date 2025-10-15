<?php
/**
 * Main interface for database interactions
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Database\DatabaseList;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\DbalInterface;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Dbal\Warning;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Query\Cache;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\Utils\SessionCache;

use function __;
use function array_column;
use function array_combine;
use function array_diff;
use function array_keys;
use function array_map;
use function array_merge;
use function array_multisort;
use function array_reverse;
use function array_shift;
use function array_slice;
use function basename;
use function closelog;
use function count;
use function defined;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function mb_strtolower;
use function microtime;
use function openlog;
use function reset;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function stripos;
use function strlen;
use function strtolower;
use function strtoupper;
use function strtr;
use function substr;
use function syslog;
use function trigger_error;
use function uasort;
use function uksort;
use function usort;

use const E_USER_WARNING;
use const LOG_INFO;
use const LOG_NDELAY;
use const LOG_PID;
use const LOG_USER;
use const SORT_ASC;
use const SORT_DESC;

/**
 * Main interface for database interactions
 */
class DatabaseInterface implements DbalInterface
{
    /**
     * Force STORE_RESULT method, ignored by classic MySQL.
     */
    public const QUERY_BUFFERED = 0;
    /**
     * Do not read all rows immediately.
     */
    public const QUERY_UNBUFFERED = 2;
    /**
     * Get session variable.
     */
    public const GETVAR_SESSION = 1;
    /**
     * Get global variable.
     */
    public const GETVAR_GLOBAL = 2;

    /**
     * User connection.
     */
    public const CONNECT_USER = 0x100;
    /**
     * Control user connection.
     */
    public const CONNECT_CONTROL = 0x101;
    /**
     * Auxiliary connection.
     *
     * Used for example for replication setup.
     */
    public const CONNECT_AUXILIARY = 0x102;

    /** @var DbiExtension */
    private $extension;

    /**
     * Opened database links
     *
     * @var array
     */
    private $links;

    /** @var array Current user and host cache */
    private $currentUser;

    /** @var array<int, array<int, string>>|null Current role and host cache */
    private $currentRoleAndHost = null;

    /** @var string|null lower_case_table_names value cache */
    private $lowerCaseTableNames = null;

    /** @var bool Whether connection is MariaDB */
    private $isMariaDb = false;
    /** @var bool Whether connection is Percona */
    private $isPercona = false;
    /** @var int Server version as number */
    private $versionInt = 55000;
    /** @var string Server version */
    private $versionString = '5.50.0';
    /** @var string Server version comment */
    private $versionComment = '';

    /** @var Types MySQL types data */
    public $types;

    /** @var Cache */
    private $cache;

    /** @var float */
    public $lastQueryExecutionTime = 0;

    /**
     * @param DbiExtension $ext Object to be used for database queries
     */
    public function __construct(DbiExtension $ext)
    {
        $this->extension = $ext;
        $this->links = [];
        if (defined('TESTSUITE')) {
            $this->links[self::CONNECT_USER] = 1;
            $this->links[self::CONNECT_CONTROL] = 2;
        }

        $this->currentUser = [];
        $this->cache = new Cache();
        $this->types = new Types($this);
    }

    /**
     * runs a query
     *
     * @param string $query               SQL query to execute
     * @param mixed  $link                optional database link to use
     * @param int    $options             optional query options
     * @param bool   $cache_affected_rows whether to cache affected rows
     */
    public function query(
        string $query,
        $link = self::CONNECT_USER,
        int $options = self::QUERY_BUFFERED,
        bool $cache_affected_rows = true
    ): ResultInterface {
        $result = $this->tryQuery($query, $link, $options, $cache_affected_rows);

        if (! $result) {
            // The following statement will exit
            Generator::mysqlDie($this->getError($link), $query);

            exit;
        }

        return $result;
    }

    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * runs a query and returns the result
     *
     * @param string $query               query to run
     * @param mixed  $link                link type
     * @param int    $options             if DatabaseInterface::QUERY_UNBUFFERED
     *                                    is provided, it will instruct the extension
     *                                    to use unbuffered mode
     * @param bool   $cache_affected_rows whether to cache affected row
     *
     * @return ResultInterface|false
     */
    public function tryQuery(
        string $query,
        $link = self::CONNECT_USER,
        int $options = self::QUERY_BUFFERED,
        bool $cache_affected_rows = true
    ) {
        $debug = isset($GLOBALS['cfg']['DBG']) && $GLOBALS['cfg']['DBG']['sql'];
        if (! isset($this->links[$link])) {
            return false;
        }

        $time = microtime(true);

        $result = $this->extension->realQuery($query, $this->links[$link], $options);

        if ($link === self::CONNECT_USER) {
            $this->lastQueryExecutionTime = microtime(true) - $time;
        }

        if ($cache_affected_rows) {
            $GLOBALS['cached_affected_rows'] = $this->affectedRows($link, false);
        }

        if ($debug) {
            $errorMessage = $this->getError($link);
            Utilities::debugLogQueryIntoSession(
                $query,
                $errorMessage !== '' ? $errorMessage : null,
                $result,
                $this->lastQueryExecutionTime
            );
            if ($GLOBALS['cfg']['DBG']['sqllog']) {
                $warningsCount = 0;
                if (isset($this->links[$link]->warning_count)) {
                    $warningsCount = $this->links[$link]->warning_count;
                }

                openlog('phpMyAdmin', LOG_NDELAY | LOG_PID, LOG_USER);

                syslog(
                    LOG_INFO,
                    sprintf(
                        'SQL[%s?route=%s]: %0.3f(W:%d,C:%s,L:0x%02X) > %s',
                        basename($_SERVER['SCRIPT_NAME']),
                        Routing::getCurrentRoute(),
                        $this->lastQueryExecutionTime,
                        $warningsCount,
                        $cache_affected_rows ? 'y' : 'n',
                        $link,
                        $query
                    )
                );
                closelog();
            }
        }

        if ($result !== false && Tracker::isActive()) {
            Tracker::handleQuery($query);
        }

        return $result;
    }

    /**
     * Send multiple SQL queries to the database server and execute the first one
     *
     * @param string $multiQuery multi query statement to execute
     * @param int    $linkIndex  index of the opened database link
     */
    public function tryMultiQuery(
        string $multiQuery = '',
        $linkIndex = self::CONNECT_USER
    ): bool {
        if (! isset($this->links[$linkIndex])) {
            return false;
        }

        return $this->extension->realMultiQuery($this->links[$linkIndex], $multiQuery);
    }

    /**
     * Executes a query as controluser.
     * The result is always buffered and never cached
     *
     * @param string $sql the query to execute
     *
     * @return ResultInterface the result set
     */
    public function queryAsControlUser(string $sql): ResultInterface
    {
        // Avoid caching of the number of rows affected; for example, this function
        // is called for tracking purposes but we want to display the correct number
        // of rows affected by the original query, not by the query generated for
        // tracking.
        return $this->query($sql, self::CONNECT_CONTROL, self::QUERY_BUFFERED, false);
    }

    /**
     * Executes a query as controluser.
     * The result is always buffered and never cached
     *
     * @param string $sql the query to execute
     *
     * @return ResultInterface|false the result set, or false if the query failed
     */
    public function tryQueryAsControlUser(string $sql)
    {
        // Avoid caching of the number of rows affected; for example, this function
        // is called for tracking purposes but we want to display the correct number
        // of rows affected by the original query, not by the query generated for
        // tracking.
        return $this->tryQuery($sql, self::CONNECT_CONTROL, self::QUERY_BUFFERED, false);
    }

    /**
     * returns array with table names for given db
     *
     * @param string $database name of database
     * @param mixed  $link     mysql link resource|object
     *
     * @return array   tables names
     */
    public function getTables(string $database, $link = self::CONNECT_USER): array
    {
        if ($database === '') {
            return [];
        }

        $tables = $this->fetchResult(
            'SHOW TABLES FROM ' . Util::backquote($database) . ';',
            null,
            0,
            $link
        );
        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($tables, 'strnatcasecmp');
        }

        return $tables;
    }

    /**
     * returns array of all tables in given db or dbs
     * this function expects unquoted names:
     * RIGHT: my_database
     * WRONG: `my_database`
     * WRONG: my\_database
     * if $tbl_is_group is true, $table is used as filter for table names
     *
     * <code>
     * $dbi->getTablesFull('my_database');
     * $dbi->getTablesFull('my_database', 'my_table'));
     * $dbi->getTablesFull('my_database', 'my_tables_', true));
     * </code>
     *
     * @param string       $database     database
     * @param string|array $table        table name(s)
     * @param bool         $tbl_is_group $table is a table group
     * @param int          $limit_offset zero-based offset for the count
     * @param bool|int     $limit_count  number of tables to return
     * @param string       $sort_by      table attribute to sort by
     * @param string       $sort_order   direction to sort (ASC or DESC)
     * @param string|null  $table_type   whether table or view
     * @param mixed        $link         link type
     *
     * @return array           list of tables in given db(s)
     *
     * @todo    move into Table
     */
    public function getTablesFull(
        string $database,
        $table = '',
        bool $tbl_is_group = false,
        int $limit_offset = 0,
        $limit_count = false,
        string $sort_by = 'Name',
        string $sort_order = 'ASC',
        ?string $table_type = null,
        $link = self::CONNECT_USER
    ): array {
        if ($limit_count === true) {
            $limit_count = $GLOBALS['cfg']['MaxTableList'];
        }

        $tables = [];
        $paging_applied = false;

        if ($limit_count && is_array($table) && $sort_by === 'Name') {
            if ($sort_order === 'DESC') {
                $table = array_reverse($table);
            }

            $table = array_slice($table, $limit_offset, $limit_count);
            $paging_applied = true;
        }

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $sql_where_table = QueryGenerator::getTableCondition(
                is_array($table) ? array_map(
                    [
                        $this,
                        'escapeString',
                    ],
                    $table
                ) : $this->escapeString($table),
                $tbl_is_group,
                $table_type
            );

            // for PMA bc:
            // `SCHEMA_FIELD_NAME` AS `SHOW_TABLE_STATUS_FIELD_NAME`
            //
            // on non-Windows servers,
            // added BINARY in the WHERE clause to force a case sensitive
            // comparison (if we are looking for the db Aa we don't want
            // to find the db aa)

            $sql = QueryGenerator::getSqlForTablesFull([$this->escapeString($database)], $sql_where_table);

            // Sort the tables
            $sql .= ' ORDER BY ' . $sort_by . ' ' . $sort_order;

            if ($limit_count && ! $paging_applied) {
                $sql .= ' LIMIT ' . $limit_count . ' OFFSET ' . $limit_offset;
            }

            /** @var mixed[][][] $tables */
            $tables = $this->fetchResult(
                $sql,
                [
                    'TABLE_SCHEMA',
                    'TABLE_NAME',
                ],
                null,
                $link
            );

            // here, we check for Mroonga engine and compute the good data_length and index_length
            // in the StructureController only we need to sum the two values as the other engines
            foreach ($tables as $one_database_name => $one_database_tables) {
                foreach ($one_database_tables as $one_table_name => $one_table_data) {
                    if ($one_table_data['Engine'] !== 'Mroonga') {
                        continue;
                    }

                    if (! StorageEngine::hasMroongaEngine()) {
                        continue;
                    }

                    [
                        $tables[$one_database_name][$one_table_name]['Data_length'],
                        $tables[$one_database_name][$one_table_name]['Index_length'],
                    ] = StorageEngine::getMroongaLengths($one_database_name, (string) $one_table_name);
                }
            }

            if ($sort_by === 'Name' && $GLOBALS['cfg']['NaturalOrder']) {
                // here, the array's first key is by schema name
                foreach ($tables as $one_database_name => $one_database_tables) {
                    uksort($one_database_tables, 'strnatcasecmp');

                    if ($sort_order === 'DESC') {
                        $one_database_tables = array_reverse($one_database_tables);
                    }

                    $tables[$one_database_name] = $one_database_tables;
                }
            } elseif ($sort_by === 'Data_length') {
                // Size = Data_length + Index_length
                foreach ($tables as $one_database_name => $one_database_tables) {
                    uasort(
                        $one_database_tables,
                        /**
                         * @param array $a
                         * @param array $b
                         */
                        static function ($a, $b) {
                            $aLength = $a['Data_length'] + $a['Index_length'];
                            $bLength = $b['Data_length'] + $b['Index_length'];

                            return $aLength <=> $bLength;
                        }
                    );

                    if ($sort_order === 'DESC') {
                        $one_database_tables = array_reverse($one_database_tables);
                    }

                    $tables[$one_database_name] = $one_database_tables;
                }
            }

            // on windows with lower_case_table_names = 1
            // MySQL returns
            // with SHOW DATABASES or information_schema.SCHEMATA: `Test`
            // but information_schema.TABLES gives `test`
            // see https://github.com/phpmyadmin/phpmyadmin/issues/8402
            $tables = $tables[$database]
                ?? $tables[mb_strtolower($database)]
                ?? [];
        }

        // If permissions are wrong on even one database directory,
        // information_schema does not return any table info for any database
        // this is why we fall back to SHOW TABLE STATUS even for MySQL >= 50002
        if ($tables === []) {
            $sql = 'SHOW TABLE STATUS FROM ' . Util::backquote($database);
            if (($table !== '' && $table !== []) || ($tbl_is_group === true) || $table_type) {
                $sql .= ' WHERE';
                $needAnd = false;
                if (($table !== '' && $table !== []) || ($tbl_is_group === true)) {
                    if (is_array($table)) {
                        $sql .= ' `Name` IN (\''
                            . implode(
                                '\', \'',
                                array_map(
                                    [
                                        $this,
                                        'escapeString',
                                    ],
                                    $table
                                )
                            ) . '\')';
                    } else {
                        $sql .= " `Name` LIKE '"
                            . $this->escapeMysqlLikeString($table, $link)
                            . "%'";
                    }

                    $needAnd = true;
                }

                if ($table_type) {
                    if ($needAnd) {
                        $sql .= ' AND';
                    }

                    if ($table_type === 'view') {
                        $sql .= " `Comment` = 'VIEW'";
                    } elseif ($table_type === 'table') {
                        $sql .= " `Comment` != 'VIEW'";
                    }
                }
            }

            $each_tables = $this->fetchResult($sql, 'Name', null, $link);

            // here, we check for Mroonga engine and compute the good data_length and index_length
            // in the StructureController only we need to sum the two values as the other engines
            foreach ($each_tables as $table_name => $table_data) {
                if ($table_data['Engine'] !== 'Mroonga') {
                    continue;
                }

                if (! StorageEngine::hasMroongaEngine()) {
                    continue;
                }

                [
                    $each_tables[$table_name]['Data_length'],
                    $each_tables[$table_name]['Index_length'],
                ] = StorageEngine::getMroongaLengths($database, $table_name);
            }

            // Sort naturally if the config allows it and we're sorting
            // the Name column.
            if ($sort_by === 'Name' && $GLOBALS['cfg']['NaturalOrder']) {
                uksort($each_tables, 'strnatcasecmp');

                if ($sort_order === 'DESC') {
                    $each_tables = array_reverse($each_tables);
                }
            } else {
                // Prepare to sort by creating array of the selected sort
                // value to pass to array_multisort

                // Size = Data_length + Index_length
                $sortValues = [];
                if ($sort_by === 'Data_length') {
                    foreach ($each_tables as $table_name => $table_data) {
                        $sortValues[$table_name] = strtolower(
                            (string) ($table_data['Data_length']
                            + $table_data['Index_length'])
                        );
                    }
                } else {
                    foreach ($each_tables as $table_name => $table_data) {
                        $sortValues[$table_name] = strtolower($table_data[$sort_by] ?? '');
                    }
                }

                if ($sortValues) {
                    // See https://stackoverflow.com/a/32461188 for the explanation of below hack
                    $keys = array_keys($each_tables);
                    if ($sort_order === 'DESC') {
                        array_multisort($sortValues, SORT_DESC, $each_tables, $keys);
                    } else {
                        array_multisort($sortValues, SORT_ASC, $each_tables, $keys);
                    }

                    $each_tables = array_combine($keys, $each_tables);
                }

                // cleanup the temporary sort array
                unset($sortValues);
            }

            if ($limit_count && ! $paging_applied) {
                $each_tables = array_slice($each_tables, $limit_offset, $limit_count, true);
            }

            $tables = Compatibility::getISCompatForGetTablesFull($each_tables, $database);
        }

        if ($tables !== []) {
            // cache table data, so Table does not require to issue SHOW TABLE STATUS again
            $this->cache->cacheTableData($database, $tables);
        }

        return $tables;
    }

    /**
     * Get VIEWs in a particular database
     *
     * @param string $db Database name to look in
     *
     * @return Table[] Set of VIEWs inside the database
     */
    public function getVirtualTables(string $db): array
    {
        /** @var string[] $tables_full */
        $tables_full = array_column($this->getTablesFull($db), 'TABLE_NAME');
        $views = [];

        foreach ($tables_full as $table) {
            $table = $this->getTable($db, $table);
            if (! $table->isView()) {
                continue;
            }

            $views[] = $table;
        }

        return $views;
    }

    /**
     * returns array with databases containing extended infos about them
     *
     * @param string|null $database     database
     * @param bool        $force_stats  retrieve stats also for MySQL < 5
     * @param int         $link         link type
     * @param string      $sort_by      column to order by
     * @param string      $sort_order   ASC or DESC
     * @param int         $limit_offset starting offset for LIMIT
     * @param bool|int    $limit_count  row count for LIMIT or true
     *                                  for $GLOBALS['cfg']['MaxDbList']
     *
     * @return array
     *
     * @todo    move into ListDatabase?
     */
    public function getDatabasesFull(
        ?string $database = null,
        bool $force_stats = false,
        $link = self::CONNECT_USER,
        string $sort_by = 'SCHEMA_NAME',
        string $sort_order = 'ASC',
        int $limit_offset = 0,
        $limit_count = false
    ): array {
        $sort_order = strtoupper($sort_order);

        if ($limit_count === true) {
            $limit_count = $GLOBALS['cfg']['MaxDbList'];
        }

        $apply_limit_and_order_manual = true;

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            /**
             * if $GLOBALS['cfg']['NaturalOrder'] is enabled, we cannot use LIMIT
             * cause MySQL does not support natural ordering,
             * we have to do it afterward
             */
            $limit = '';
            if (! $GLOBALS['cfg']['NaturalOrder']) {
                if ($limit_count) {
                    $limit = ' LIMIT ' . $limit_count . ' OFFSET ' . $limit_offset;
                }

                $apply_limit_and_order_manual = false;
            }

            // get table information from information_schema
            $sqlWhereSchema = '';
            if ($database !== null) {
                $sqlWhereSchema = 'WHERE `SCHEMA_NAME` LIKE \''
                    . $this->escapeString($database, $link) . '\'';
            }

            $sql = QueryGenerator::getInformationSchemaDatabasesFullRequest(
                $force_stats,
                $sqlWhereSchema,
                $sort_by,
                $sort_order,
                $limit
            );

            $databases = $this->fetchResult($sql, 'SCHEMA_NAME', null, $link);

            $mysql_error = $this->getError($link);
            if (! count($databases) && isset($GLOBALS['errno'])) {
                Generator::mysqlDie($mysql_error, $sql);
            }

            // display only databases also in official database list
            // f.e. to apply hide_db and only_db
            $drops = array_diff(
                array_keys($databases),
                (array) $GLOBALS['dblist']->databases
            );
            foreach ($drops as $drop) {
                unset($databases[$drop]);
            }
        } else {
            $databases = [];
            foreach ($GLOBALS['dblist']->databases as $database_name) {
                // Compatibility with INFORMATION_SCHEMA output
                $databases[$database_name]['SCHEMA_NAME'] = $database_name;

                $databases[$database_name]['DEFAULT_COLLATION_NAME'] = $this->getDbCollation($database_name);

                if (! $force_stats) {
                    continue;
                }

                // get additional info about tables
                $databases[$database_name]['SCHEMA_TABLES'] = 0;
                $databases[$database_name]['SCHEMA_TABLE_ROWS'] = 0;
                $databases[$database_name]['SCHEMA_DATA_LENGTH'] = 0;
                $databases[$database_name]['SCHEMA_MAX_DATA_LENGTH'] = 0;
                $databases[$database_name]['SCHEMA_INDEX_LENGTH'] = 0;
                $databases[$database_name]['SCHEMA_LENGTH'] = 0;
                $databases[$database_name]['SCHEMA_DATA_FREE'] = 0;

                $res = $this->query(
                    'SHOW TABLE STATUS FROM '
                    . Util::backquote($database_name) . ';'
                );

                while ($row = $res->fetchAssoc()) {
                    $databases[$database_name]['SCHEMA_TABLES']++;
                    $databases[$database_name]['SCHEMA_TABLE_ROWS'] += $row['Rows'];
                    $databases[$database_name]['SCHEMA_DATA_LENGTH'] += $row['Data_length'];
                    $databases[$database_name]['SCHEMA_MAX_DATA_LENGTH'] += $row['Max_data_length'];
                    $databases[$database_name]['SCHEMA_INDEX_LENGTH'] += $row['Index_length'];

                    // for InnoDB, this does not contain the number of
                    // overhead bytes but the total free space
                    if ($row['Engine'] !== 'InnoDB') {
                        $databases[$database_name]['SCHEMA_DATA_FREE'] += $row['Data_free'];
                    }

                    $databases[$database_name]['SCHEMA_LENGTH'] += $row['Data_length'] + $row['Index_length'];
                }

                unset($res);
            }
        }

        /**
         * apply limit and order manually now
         * (caused by older MySQL < 5 or $GLOBALS['cfg']['NaturalOrder'])
         */
        if ($apply_limit_and_order_manual) {
            usort(
                $databases,
                static function ($a, $b) use ($sort_by, $sort_order) {
                    return Utilities::usortComparisonCallback($a, $b, $sort_by, $sort_order);
                }
            );

            /**
             * now apply limit
             */
            if ($limit_count) {
                $databases = array_slice($databases, $limit_offset, $limit_count);
            }
        }

        return $databases;
    }

    /**
     * returns detailed array with all columns for sql
     *
     * @param string $sql_query    target SQL query to get columns
     * @param array  $view_columns alias for columns
     *
     * @return array
     * @psalm-return list<array<string, mixed>>
     */
    public function getColumnMapFromSql(string $sql_query, array $view_columns = []): array
    {
        $result = $this->tryQuery($sql_query);

        if ($result === false) {
            return [];
        }

        $meta = $this->getFieldsMeta($result);

        $column_map = [];
        $nbColumns = count($view_columns);

        foreach ($meta as $i => $field) {
            $map = [
                'table_name' => $field->table,
                'refering_column' => $field->name,
            ];

            if ($nbColumns >= $i && isset($view_columns[$i])) {
                $map['real_column'] = $view_columns[$i];
            }

            $column_map[] = $map;
        }

        return $column_map;
    }

    /**
     * returns detailed array with all columns for given table in database,
     * or all tables/databases
     *
     * @param string|null $database name of database
     * @param string|null $table    name of table to retrieve columns from
     * @param string|null $column   name of specific column
     * @param mixed       $link     mysql link resource
     *
     * @return array
     */
    public function getColumnsFull(
        ?string $database = null,
        ?string $table = null,
        ?string $column = null,
        $link = self::CONNECT_USER
    ): array {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            [$sql, $arrayKeys] = QueryGenerator::getInformationSchemaColumnsFullRequest(
                $database !== null ? $this->escapeString($database, $link) : null,
                $table !== null ? $this->escapeString($table, $link) : null,
                $column !== null ? $this->escapeString($column, $link) : null
            );

            return $this->fetchResult($sql, $arrayKeys, null, $link);
        }

        $columns = [];
        if ($database === null) {
            foreach ($GLOBALS['dblist']->databases as $database) {
                $columns[$database] = $this->getColumnsFull($database, null, null, $link);
            }

            return $columns;
        }

        if ($table === null) {
            $tables = $this->getTables($database);
            foreach ($tables as $table) {
                $columns[$table] = $this->getColumnsFull($database, $table, null, $link);
            }

            return $columns;
        }

        $sql = 'SHOW FULL COLUMNS FROM '
            . Util::backquote($database) . '.' . Util::backquote($table);
        if ($column !== null) {
            $sql .= " LIKE '" . $this->escapeString($column, $link) . "'";
        }

        $columns = $this->fetchResult($sql, 'Field', null, $link);

        $columns = Compatibility::getISCompatForGetColumnsFull($columns, $database, $table);

        if ($column !== null) {
            return reset($columns);
        }

        return $columns;
    }

    /**
     * Returns description of a $column in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param string $column   name of column
     * @param bool   $full     whether to return full info or only column names
     * @param int    $link     link type
     *
     * @return array flat array description
     */
    public function getColumn(
        string $database,
        string $table,
        string $column,
        bool $full = false,
        $link = self::CONNECT_USER
    ): array {
        $sql = QueryGenerator::getColumnsSql(
            $database,
            $table,
            $this->escapeMysqlLikeString($column),
            $full
        );
        /** @var array<string, array> $fields */
        $fields = $this->fetchResult($sql, 'Field', null, $link);

        $columns = $this->attachIndexInfoToColumns($database, $table, $fields);

        return array_shift($columns) ?? [];
    }

    /**
     * Returns descriptions of columns in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param bool   $full     whether to return full info or only column names
     * @param int    $link     link type
     *
     * @return array<string, array> array indexed by column names
     */
    public function getColumns(
        string $database,
        string $table,
        bool $full = false,
        $link = self::CONNECT_USER
    ): array {
        $sql = QueryGenerator::getColumnsSql(
            $database,
            $table,
            null,
            $full
        );
        /** @var array<string, array> $fields */
        $fields = $this->fetchResult($sql, 'Field', null, $link);

        return $this->attachIndexInfoToColumns($database, $table, $fields);
    }

    /**
     * Attach index information to the column definition
     *
     * @param string               $database name of database
     * @param string               $table    name of table to retrieve columns from
     * @param array<string, array> $fields   column array indexed by their names
     *
     * @return array<string, array> Column defintions with index information
     */
    private function attachIndexInfoToColumns(
        string $database,
        string $table,
        array $fields
    ): array {
        if (! $fields) {
            return [];
        }

        // Check if column is a part of multiple-column index and set its 'Key'.
        $indexes = Index::getFromTable($table, $database);
        foreach ($fields as $field => $field_data) {
            if (! empty($field_data['Key'])) {
                continue;
            }

            foreach ($indexes as $index) {
                if (! $index->hasColumn($field)) {
                    continue;
                }

                $index_columns = $index->getColumns();
                if ($index_columns[$field]->getSeqInIndex() <= 1) {
                    continue;
                }

                if ($index->isUnique()) {
                    $fields[$field]['Key'] = 'UNI';
                } else {
                    $fields[$field]['Key'] = 'MUL';
                }
            }
        }

        return $fields;
    }

    /**
     * Returns all column names in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param mixed  $link     mysql link resource
     *
     * @return string[]
     */
    public function getColumnNames(
        string $database,
        string $table,
        $link = self::CONNECT_USER
    ): array {
        $sql = QueryGenerator::getColumnsSql($database, $table);

        // We only need the 'Field' column which contains the table's column names
        return $this->fetchResult($sql, null, 'Field', $link);
    }

    /**
     * Returns indexes of a table
     *
     * @param string $database name of database
     * @param string $table    name of the table whose indexes are to be retrieved
     * @param mixed  $link     mysql link resource
     *
     * @return array
     */
    public function getTableIndexes(
        string $database,
        string $table,
        $link = self::CONNECT_USER
    ): array {
        $sql = QueryGenerator::getTableIndexesSql($database, $table);

        return $this->fetchResult($sql, null, null, $link);
    }

    /**
     * returns value of given mysql server variable
     *
     * @param string $var  mysql server variable name
     * @param int    $type DatabaseInterface::GETVAR_SESSION |
     *                     DatabaseInterface::GETVAR_GLOBAL
     * @param int    $link mysql link resource|object
     *
     * @return false|string|null value for mysql server variable
     */
    public function getVariable(
        string $var,
        int $type = self::GETVAR_SESSION,
        $link = self::CONNECT_USER
    ) {
        switch ($type) {
            case self::GETVAR_SESSION:
                $modifier = ' SESSION';
                break;
            case self::GETVAR_GLOBAL:
                $modifier = ' GLOBAL';
                break;
            default:
                $modifier = '';
        }

        return $this->fetchValue('SHOW' . $modifier . ' VARIABLES LIKE \'' . $var . '\';', 1, $link);
    }

    /**
     * Sets new value for a variable if it is different from the current value
     *
     * @param string $var   variable name
     * @param string $value value to set
     * @param int    $link  mysql link resource|object
     */
    public function setVariable(
        string $var,
        string $value,
        $link = self::CONNECT_USER
    ): bool {
        $current_value = $this->getVariable($var, self::GETVAR_SESSION, $link);
        if ($current_value == $value) {
            return true;
        }

        return (bool) $this->query('SET ' . $var . ' = ' . $value . ';', $link);
    }

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnect(): void
    {
        $version = $this->fetchSingleRow('SELECT @@version, @@version_comment');

        if (is_array($version)) {
            $this->setVersion($version);
        }

        if ($this->versionInt > 50503) {
            $default_charset = 'utf8mb4';
            $default_collation = 'utf8mb4_general_ci';
        } else {
            $default_charset = 'utf8';
            $default_collation = 'utf8_general_ci';
        }

        $GLOBALS['collation_connection'] = $default_collation;
        $GLOBALS['charset_connection'] = $default_charset;
        $this->query(sprintf('SET NAMES \'%s\' COLLATE \'%s\';', $default_charset, $default_collation));

        /* Locale for messages */
        $locale = LanguageManager::getInstance()->getCurrentLanguage()->getMySQLLocale();
        if ($locale) {
            $this->tryQuery("SET lc_messages = '" . $locale . "';");
        }

        // Set timezone for the session, if required.
        if ($GLOBALS['cfg']['Server']['SessionTimeZone'] != '') {
            $sql_query_tz = 'SET ' . Util::backquote('time_zone') . ' = '
                . '\''
                . $this->escapeString($GLOBALS['cfg']['Server']['SessionTimeZone'])
                . '\'';

            if (! $this->tryQuery($sql_query_tz)) {
                $error_message_tz = sprintf(
                    __(
                        'Unable to use timezone "%1$s" for server %2$d. '
                        . 'Please check your configuration setting for '
                        . '[em]$cfg[\'Servers\'][%3$d][\'SessionTimeZone\'][/em]. '
                        . 'phpMyAdmin is currently using the default time zone '
                        . 'of the database server.'
                    ),
                    $GLOBALS['cfg']['Server']['SessionTimeZone'],
                    $GLOBALS['server'],
                    $GLOBALS['server']
                );

                trigger_error($error_message_tz, E_USER_WARNING);
            }
        }

        /* Loads closest context to this version. */
        Context::loadClosest(($this->isMariaDb ? 'MariaDb' : 'MySql') . $this->versionInt);

        /**
         * the DatabaseList class as a stub for the ListDatabase class
         */
        $GLOBALS['dblist'] = new DatabaseList();
    }

    /**
     * Sets collation connection for user link
     *
     * @param string $collation collation to set
     */
    public function setCollation(string $collation): void
    {
        $charset = $GLOBALS['charset_connection'];
        /* Automatically adjust collation if not supported by server */
        if ($charset === 'utf8' && str_starts_with($collation, 'utf8mb4_')) {
            $collation = 'utf8_' . substr($collation, 8);
        }

        $result = $this->tryQuery(
            "SET collation_connection = '"
            . $this->escapeString($collation)
            . "';"
        );

        if ($result === false) {
            trigger_error(
                __('Failed to set configured collation connection!'),
                E_USER_WARNING
            );

            return;
        }

        $GLOBALS['collation_connection'] = $collation;
    }

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnectControl(Relation $relation): void
    {
        // If Zero configuration mode enabled, check PMA tables in current db.
        if ($GLOBALS['cfg']['ZeroConf'] != true) {
            return;
        }

        /**
         * the DatabaseList class as a stub for the ListDatabase class
         */
        $GLOBALS['dblist'] = new DatabaseList();

        $relation->initRelationParamsCache();
    }

    /**
     * returns a single value from the given result or query,
     * if the query or the result has more than one row or field
     * the first field of the first row is returned
     *
     * <code>
     * $sql = 'SELECT `name` FROM `user` WHERE `id` = 123';
     * $user_name = $dbi->fetchValue($sql);
     * // produces
     * // $user_name = 'John Doe'
     * </code>
     *
     * @param string     $query The query to execute
     * @param int|string $field field to fetch the value from,
     *                          starting at 0, with 0 being default
     * @param int        $link  link type
     *
     * @return string|false|null value of first field in first row from result or false if not found
     */
    public function fetchValue(
        string $query,
        $field = 0,
        $link = self::CONNECT_USER
    ) {
        $result = $this->tryQuery($query, $link, self::QUERY_BUFFERED, false);
        if ($result === false) {
            return false;
        }

        return $result->fetchValue($field);
    }

    /**
     * Returns only the first row from the result or null if result is empty.
     *
     * <code>
     * $sql = 'SELECT * FROM `user` WHERE `id` = 123';
     * $user = $dbi->fetchSingleRow($sql);
     * // produces
     * // $user = array('id' => 123, 'name' => 'John Doe')
     * </code>
     *
     * @param string $query The query to execute
     * @param string $type  NUM|ASSOC|BOTH returned array should either numeric
     *                      associative or both
     * @param int    $link  link type
     * @psalm-param  DatabaseInterface::FETCH_NUM|DatabaseInterface::FETCH_ASSOC $type
     */
    public function fetchSingleRow(
        string $query,
        string $type = DbalInterface::FETCH_ASSOC,
        $link = self::CONNECT_USER
    ): ?array {
        $result = $this->tryQuery($query, $link, self::QUERY_BUFFERED, false);
        if ($result === false) {
            return null;
        }

        return $this->fetchByMode($result, $type) ?: null;
    }

    /**
     * Returns row or element of a row
     *
     * @param array|string    $row   Row to process
     * @param string|int|null $value Which column to return
     *
     * @return mixed
     */
    private function fetchValueOrValueByIndex($row, $value)
    {
        return $value === null ? $row : $row[$value];
    }

    /**
     * returns array of rows with numeric or associative keys
     *
     * @param ResultInterface $result result set identifier
     * @param string          $mode   either self::FETCH_NUM, self::FETCH_ASSOC or self::FETCH_BOTH
     * @psalm-param self::FETCH_NUM|self::FETCH_ASSOC $mode
     */
    private function fetchByMode(ResultInterface $result, string $mode): array
    {
        if ($mode === self::FETCH_NUM) {
            return $result->fetchRow();
        }

        return $result->fetchAssoc();
    }

    /**
     * returns all rows in the resultset in one array
     *
     * <code>
     * $sql = 'SELECT * FROM `user`';
     * $users = $dbi->fetchResult($sql);
     * // produces
     * // $users[] = array('id' => 123, 'name' => 'John Doe')
     *
     * $sql = 'SELECT `id`, `name` FROM `user`';
     * $users = $dbi->fetchResult($sql, 'id');
     * // produces
     * // $users['123'] = array('id' => 123, 'name' => 'John Doe')
     *
     * $sql = 'SELECT `id`, `name` FROM `user`';
     * $users = $dbi->fetchResult($sql, 0);
     * // produces
     * // $users['123'] = array(0 => 123, 1 => 'John Doe')
     *
     * $sql = 'SELECT `id`, `name` FROM `user`';
     * $users = $dbi->fetchResult($sql, 'id', 'name');
     * // or
     * $users = $dbi->fetchResult($sql, 0, 1);
     * // produces
     * // $users['123'] = 'John Doe'
     *
     * $sql = 'SELECT `name` FROM `user`';
     * $users = $dbi->fetchResult($sql);
     * // produces
     * // $users[] = 'John Doe'
     *
     * $sql = 'SELECT `group`, `name` FROM `user`'
     * $users = $dbi->fetchResult($sql, array('group', null), 'name');
     * // produces
     * // $users['admin'][] = 'John Doe'
     *
     * $sql = 'SELECT `group`, `name` FROM `user`'
     * $users = $dbi->fetchResult($sql, array('group', 'name'), 'id');
     * // produces
     * // $users['admin']['John Doe'] = '123'
     * </code>
     *
     * @param string                $query query to execute
     * @param string|int|array|null $key   field-name or offset
     *                                     used as key for array
     *                                     or array of those
     * @param string|int|null       $value value-name or offset
     *                                     used as value for array
     * @param int                   $link  link type
     *
     * @return array resultrows or values indexed by $key
     */
    public function fetchResult(
        string $query,
        $key = null,
        $value = null,
        $link = self::CONNECT_USER
    ): array {
        $resultrows = [];

        $result = $this->tryQuery($query, $link, self::QUERY_BUFFERED, false);

        // return empty array if result is empty or false
        if ($result === false) {
            return $resultrows;
        }

        $fetch_function = self::FETCH_ASSOC;

        if ($key === null) {
            // no nested array if only one field is in result
            if ($result->numFields() === 1) {
                $value = 0;
                $fetch_function = self::FETCH_NUM;
            }

            while ($row = $this->fetchByMode($result, $fetch_function)) {
                $resultrows[] = $this->fetchValueOrValueByIndex($row, $value);
            }
        } elseif (is_array($key)) {
            while ($row = $this->fetchByMode($result, $fetch_function)) {
                $result_target =& $resultrows;
                foreach ($key as $key_index) {
                    if ($key_index === null) {
                        $result_target =& $result_target[];
                        continue;
                    }

                    if (! isset($result_target[$row[$key_index]])) {
                        $result_target[$row[$key_index]] = [];
                    }

                    $result_target =& $result_target[$row[$key_index]];
                }

                $result_target = $this->fetchValueOrValueByIndex($row, $value);
            }
        } else {
            // if $key is an integer use non associative mysql fetch function
            if (is_int($key)) {
                $fetch_function = self::FETCH_NUM;
            }

            while ($row = $this->fetchByMode($result, $fetch_function)) {
                $resultrows[$row[$key]] = $this->fetchValueOrValueByIndex($row, $value);
            }
        }

        return $resultrows;
    }

    /**
     * Get supported SQL compatibility modes
     *
     * @return array supported SQL compatibility modes
     */
    public function getCompatibilities(): array
    {
        return [
            'NONE',
            'ANSI',
            'DB2',
            'MAXDB',
            'MYSQL323',
            'MYSQL40',
            'MSSQL',
            'ORACLE',
            // removed; in MySQL 5.0.33, this produces exports that
            // can't be read by POSTGRESQL (see our bug #1596328)
            // 'POSTGRESQL',
            'TRADITIONAL',
        ];
    }

    /**
     * returns warnings for last query
     *
     * @param int $link link type
     *
     * @return Warning[] warnings
     */
    public function getWarnings($link = self::CONNECT_USER): array
    {
        $result = $this->tryQuery('SHOW WARNINGS', $link, 0, false);
        if ($result === false) {
            return [];
        }

        $warnings = [];
        while ($row = $result->fetchAssoc()) {
            $warnings[] = Warning::fromArray($row);
        }

        return $warnings;
    }

    /**
     * returns an array of PROCEDURE or FUNCTION names for a db
     *
     * @param string $db    db name
     * @param string $which PROCEDURE | FUNCTION
     * @param int    $link  link type
     *
     * @return array the procedure names or function names
     */
    public function getProceduresOrFunctions(
        string $db,
        string $which,
        $link = self::CONNECT_USER
    ): array {
        $shows = $this->fetchResult('SHOW ' . $which . ' STATUS;', null, null, $link);
        $result = [];
        foreach ($shows as $one_show) {
            if ($one_show['Db'] != $db || $one_show['Type'] != $which) {
                continue;
            }

            $result[] = $one_show['Name'];
        }

        return $result;
    }

    /**
     * returns the definition of a specific PROCEDURE, FUNCTION, EVENT or VIEW
     *
     * @param string $db    db name
     * @param string $which PROCEDURE | FUNCTION | EVENT | VIEW
     * @param string $name  the procedure|function|event|view name
     * @param int    $link  link type
     *
     * @return string|null the definition
     */
    public function getDefinition(
        string $db,
        string $which,
        string $name,
        $link = self::CONNECT_USER
    ): ?string {
        $returned_field = [
            'PROCEDURE' => 'Create Procedure',
            'FUNCTION' => 'Create Function',
            'EVENT' => 'Create Event',
            'VIEW' => 'Create View',
        ];
        $query = 'SHOW CREATE ' . $which . ' '
            . Util::backquote($db) . '.'
            . Util::backquote($name);
        $result = $this->fetchValue($query, $returned_field[$which], $link);

        return is_string($result) ? $result : null;
    }

    /**
     * returns details about the PROCEDUREs or FUNCTIONs for a specific database
     * or details about a specific routine
     *
     * @param string      $db    db name
     * @param string|null $which PROCEDURE | FUNCTION or null for both
     * @param string      $name  name of the routine (to fetch a specific routine)
     *
     * @return array information about PROCEDUREs or FUNCTIONs
     */
    public function getRoutines(
        string $db,
        ?string $which = null,
        string $name = ''
    ): array {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaRoutinesRequest(
                $this->escapeString($db),
                isset($which) && in_array($which, ['FUNCTION', 'PROCEDURE']) ? $which : null,
                empty($name) ? null : $this->escapeString($name)
            );
            $routines = $this->fetchResult($query);
        } else {
            $routines = [];

            if ($which === 'FUNCTION' || $which == null) {
                $query = 'SHOW FUNCTION STATUS'
                    . " WHERE `Db` = '" . $this->escapeString($db) . "'";
                if ($name) {
                    $query .= " AND `Name` = '"
                        . $this->escapeString($name) . "'";
                }

                $routines = $this->fetchResult($query);
            }

            if ($which === 'PROCEDURE' || $which == null) {
                $query = 'SHOW PROCEDURE STATUS'
                    . " WHERE `Db` = '" . $this->escapeString($db) . "'";
                if ($name) {
                    $query .= " AND `Name` = '"
                        . $this->escapeString($name) . "'";
                }

                $routines = array_merge($routines, $this->fetchResult($query));
            }
        }

        $ret = [];
        foreach ($routines as $routine) {
            $ret[] = [
                'db' => $routine['Db'],
                'name' => $routine['Name'],
                'type' => $routine['Type'],
                'definer' => $routine['Definer'],
                'returns' => $routine['DTD_IDENTIFIER'] ?? '',
            ];
        }

        // Sort results by name
        $name = array_column($ret, 'name');
        array_multisort($name, SORT_ASC, $ret);

        return $ret;
    }

    /**
     * returns details about the EVENTs for a specific database
     *
     * @param string $db   db name
     * @param string $name event name
     *
     * @return array information about EVENTs
     */
    public function getEvents(string $db, string $name = ''): array
    {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaEventsRequest(
                $this->escapeString($db),
                empty($name) ? null : $this->escapeString($name)
            );
        } else {
            $query = 'SHOW EVENTS FROM ' . Util::backquote($db);
            if ($name) {
                $query .= " WHERE `Name` = '"
                    . $this->escapeString($name) . "'";
            }
        }

        $result = [];
        $events = $this->fetchResult($query);

        foreach ($events as $event) {
            $result[] = [
                'name' => $event['Name'],
                'type' => $event['Type'],
                'status' => $event['Status'],
            ];
        }

        // Sort results by name
        $name = array_column($result, 'name');
        array_multisort($name, SORT_ASC, $result);

        return $result;
    }

    /**
     * returns details about the TRIGGERs for a specific table or database
     *
     * @param string $db        db name
     * @param string $table     table name
     * @param string $delimiter the delimiter to use (may be empty)
     *
     * @return array information about triggers (may be empty)
     */
    public function getTriggers(string $db, string $table = '', string $delimiter = '//'): array
    {
        $result = [];
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaTriggersRequest(
                $this->escapeString($db),
                empty($table) ? null : $this->escapeString($table)
            );
        } else {
            $query = 'SHOW TRIGGERS FROM ' . Util::backquote($db);
            if ($table) {
                $query .= " LIKE '" . $this->escapeString($table) . "';";
            }
        }

        $triggers = $this->fetchResult($query);

        foreach ($triggers as $trigger) {
            if ($GLOBALS['cfg']['Server']['DisableIS']) {
                $trigger['TRIGGER_NAME'] = $trigger['Trigger'];
                $trigger['ACTION_TIMING'] = $trigger['Timing'];
                $trigger['EVENT_MANIPULATION'] = $trigger['Event'];
                $trigger['EVENT_OBJECT_TABLE'] = $trigger['Table'];
                $trigger['ACTION_STATEMENT'] = $trigger['Statement'];
                $trigger['DEFINER'] = $trigger['Definer'];
            }

            $one_result = [];
            $one_result['name'] = $trigger['TRIGGER_NAME'];
            $one_result['table'] = $trigger['EVENT_OBJECT_TABLE'];
            $one_result['action_timing'] = $trigger['ACTION_TIMING'];
            $one_result['event_manipulation'] = $trigger['EVENT_MANIPULATION'];
            $one_result['definition'] = $trigger['ACTION_STATEMENT'];
            $one_result['definer'] = $trigger['DEFINER'];

            // do not prepend the schema name; this way, importing the
            // definition into another schema will work
            $one_result['full_trigger_name'] = Util::backquote($trigger['TRIGGER_NAME']);
            $one_result['drop'] = 'DROP TRIGGER IF EXISTS '
                . $one_result['full_trigger_name'];
            $one_result['create'] = 'CREATE TRIGGER '
                . $one_result['full_trigger_name'] . ' '
                . $trigger['ACTION_TIMING'] . ' '
                . $trigger['EVENT_MANIPULATION']
                . ' ON ' . Util::backquote($trigger['EVENT_OBJECT_TABLE'])
                . "\n" . ' FOR EACH ROW '
                . $trigger['ACTION_STATEMENT'] . "\n" . $delimiter . "\n";

            $result[] = $one_result;
        }

        // Sort results by name
        $name = array_column($result, 'name');
        array_multisort($name, SORT_ASC, $result);

        return $result;
    }

    /**
     * gets the current user with host
     *
     * @return string the current user i.e. user@host
     */
    public function getCurrentUser(): string
    {
        if (SessionCache::has('mysql_cur_user')) {
            return SessionCache::get('mysql_cur_user');
        }

        $user = $this->fetchValue('SELECT CURRENT_USER();');
        if ($user !== false) {
            SessionCache::set('mysql_cur_user', $user);

            return $user;
        }

        return '@';
    }

    /**
     * gets the current role with host. Role maybe multiple separated by comma
     * Support start from MySQL 8.x / MariaDB 10.0.5
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/roles.html
     * @see https://dev.mysql.com/doc/refman/8.0/en/information-functions.html#function_current-role
     * @see https://mariadb.com/kb/en/mariadb-1005-release-notes/#newly-implemented-features
     * @see https://mariadb.com/kb/en/roles_overview/
     *
     * @return array<int, array<int, string>> the current roles i.e. array of role@host
     */
    public function getCurrentRoles(): array
    {
        if (($this->isMariaDB() && $this->getVersion() < 100500) || $this->getVersion() < 80000) {
            return [];
        }

        if (SessionCache::has('mysql_cur_role')) {
            return SessionCache::get('mysql_cur_role');
        }

        $role = $this->fetchValue('SELECT CURRENT_ROLE();');
        if ($role === false || $role === null || $role === 'NONE') {
            return [];
        }

        $role = array_map('trim', explode(',', str_replace('`', '', $role)));
        SessionCache::set('mysql_cur_role', $role);

        return $role;
    }

    public function isSuperUser(): bool
    {
        if (SessionCache::has('is_superuser')) {
            return (bool) SessionCache::get('is_superuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $result = $this->tryQuery('SELECT 1 FROM mysql.user LIMIT 1');
        $isSuperUser = false;

        if ($result) {
            $isSuperUser = (bool) $result->numRows();
        }

        SessionCache::set('is_superuser', $isSuperUser);

        return $isSuperUser;
    }

    public function isGrantUser(): bool
    {
        global $cfg;

        if (SessionCache::has('is_grantuser')) {
            return (bool) SessionCache::get('is_grantuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $hasGrantPrivilege = false;

        if ($cfg['Server']['DisableIS']) {
            $grants = $this->getCurrentUserGrants();

            foreach ($grants as $grant) {
                if (str_contains($grant, 'WITH GRANT OPTION')) {
                    $hasGrantPrivilege = true;
                    break;
                }
            }

            SessionCache::set('is_grantuser', $hasGrantPrivilege);

            return $hasGrantPrivilege;
        }

        $collation = $this->getServerCollation();

        [$user, $host] = $this->getCurrentUserAndHost();
        $query = QueryGenerator::getInformationSchemaDataForGranteeRequest($user, $host, $collation);
        $result = $this->tryQuery($query);

        if ($result) {
            $hasGrantPrivilege = (bool) $result->numRows();
        }

        if (! $hasGrantPrivilege) {
            foreach ($this->getCurrentRolesAndHost() as [$role, $roleHost]) {
                $query = QueryGenerator::getInformationSchemaDataForGranteeRequest($role, $roleHost ?? '', $collation);
                $result = $this->tryQuery($query);

                if ($result) {
                    $hasGrantPrivilege = (bool) $result->numRows();
                }

                if ($hasGrantPrivilege) {
                    break;
                }
            }
        }

        SessionCache::set('is_grantuser', $hasGrantPrivilege);

        return $hasGrantPrivilege;
    }

    public function isCreateUser(): bool
    {
        global $cfg;

        if (SessionCache::has('is_createuser')) {
            return (bool) SessionCache::get('is_createuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $hasCreatePrivilege = false;

        if ($cfg['Server']['DisableIS']) {
            $grants = $this->getCurrentUserGrants();

            foreach ($grants as $grant) {
                if (str_contains($grant, 'ALL PRIVILEGES ON *.*') || str_contains($grant, 'CREATE USER')) {
                    $hasCreatePrivilege = true;
                    break;
                }
            }

            SessionCache::set('is_createuser', $hasCreatePrivilege);

            return $hasCreatePrivilege;
        }

        $collation = $this->getServerCollation();

        [$user, $host] = $this->getCurrentUserAndHost();
        $query = QueryGenerator::getInformationSchemaDataForCreateRequest($user, $host, $collation);
        $result = $this->tryQuery($query);

        if ($result) {
            $hasCreatePrivilege = (bool) $result->numRows();
        }

        if (! $hasCreatePrivilege) {
            foreach ($this->getCurrentRolesAndHost() as [$role, $roleHost]) {
                $query = QueryGenerator::getInformationSchemaDataForCreateRequest($role, $roleHost ?? '', $collation);
                $result = $this->tryQuery($query);

                if ($result) {
                    $hasCreatePrivilege = (bool) $result->numRows();
                }

                if ($hasCreatePrivilege) {
                    break;
                }
            }
        }

        SessionCache::set('is_createuser', $hasCreatePrivilege);

        return $hasCreatePrivilege;
    }

    public function isConnected(): bool
    {
        return isset($this->links[self::CONNECT_USER]);
    }

    private function getCurrentUserGrants(): array
    {
        return $this->fetchResult('SHOW GRANTS FOR CURRENT_USER();');
    }

    /**
     * Get the current user and host
     *
     * @return array array of username and hostname
     */
    public function getCurrentUserAndHost(): array
    {
        if (count($this->currentUser) === 0) {
            $user = $this->getCurrentUser();
            $this->currentUser = explode('@', $user);
        }

        return $this->currentUser;
    }

    /**
     * Get the current role and host.
     *
     * @return array<int, array<int, string>> array of role and hostname
     */
    public function getCurrentRolesAndHost(): array
    {
        if ($this->currentRoleAndHost === null) {
            $roles = $this->getCurrentRoles();

            $this->currentRoleAndHost = array_map(static function (string $role) {
                return explode('@', $role);
            }, $roles);
        }

        return $this->currentRoleAndHost;
    }

    /**
     * Returns value for lower_case_table_names variable
     *
     * @return string
     */
    public function getLowerCaseNames()
    {
        if ($this->lowerCaseTableNames === null) {
            $this->lowerCaseTableNames = $this->fetchValue('SELECT @@lower_case_table_names') ?: '';
        }

        return $this->lowerCaseTableNames;
    }

    /**
     * connects to the database server
     *
     * @param int        $mode   Connection mode on of CONNECT_USER, CONNECT_CONTROL
     *                           or CONNECT_AUXILIARY.
     * @param array|null $server Server information like host/port/socket/persistent
     * @param int|null   $target How to store connection link, defaults to $mode
     *
     * @return mixed false on error or a connection object on success
     */
    public function connect(int $mode, ?array $server = null, ?int $target = null)
    {
        [$user, $password, $server] = Config::getConnectionParams($mode, $server);

        if ($target === null) {
            $target = $mode;
        }

        if ($user === null || $password === null) {
            trigger_error(
                __('Missing connection parameters!'),
                E_USER_WARNING
            );

            return false;
        }

        // Do not show location and backtrace for connection errors
        $GLOBALS['errorHandler']->setHideLocation(true);
        $result = $this->extension->connect($user, $password, $server);
        $GLOBALS['errorHandler']->setHideLocation(false);

        if ($result) {
            $this->links[$target] = $result;
            /* Run post connect for user connections */
            if ($target == self::CONNECT_USER) {
                $this->postConnect();
            }

            return $result;
        }

        if ($mode == self::CONNECT_CONTROL) {
            trigger_error(
                __(
                    'Connection for controluser as defined in your configuration failed.'
                ),
                E_USER_WARNING
            );

            return false;
        }

        if ($mode == self::CONNECT_AUXILIARY) {
            // Do not go back to main login if connection failed
            // (currently used only in unit testing)
            return false;
        }

        return $result;
    }

    /**
     * selects given database
     *
     * @param string|DatabaseName $dbname database name to select
     * @param int                 $link   link type
     */
    public function selectDb($dbname, $link = self::CONNECT_USER): bool
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->selectDb($dbname, $this->links[$link]);
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @param int $link link type
     */
    public function moreResults($link = self::CONNECT_USER): bool
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->moreResults($this->links[$link]);
    }

    /**
     * Prepare next result from multi_query
     *
     * @param int $link link type
     */
    public function nextResult($link = self::CONNECT_USER): bool
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->nextResult($this->links[$link]);
    }

    /**
     * Store the result returned from multi query
     *
     * @param int $link link type
     *
     * @return ResultInterface|false false when empty results / result set when not empty
     */
    public function storeResult($link = self::CONNECT_USER)
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->storeResult($this->links[$link]);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @param int $link link type
     *
     * @return string|bool type of connection used
     */
    public function getHostInfo($link = self::CONNECT_USER)
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->getHostInfo($this->links[$link]);
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param int $link link type
     *
     * @return int|bool version of the MySQL protocol used
     */
    public function getProtoInfo($link = self::CONNECT_USER)
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->getProtoInfo($this->links[$link]);
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo(): string
    {
        return $this->extension->getClientInfo();
    }

    /**
     * Returns last error message or an empty string if no errors occurred.
     *
     * @param int $link link type
     */
    public function getError($link = self::CONNECT_USER): string
    {
        if (! isset($this->links[$link])) {
            return '';
        }

        return $this->extension->getError($this->links[$link]);
    }

    /**
     * returns the number of rows returned by last query
     * used with tryQuery as it accepts false
     *
     * @param string $query query to run
     *
     * @return string|int
     * @psalm-return int|numeric-string
     */
    public function queryAndGetNumRows(string $query)
    {
        $result = $this->tryQuery($query);

        if (! $result) {
            return 0;
        }

        return $result->numRows();
    }

    /**
     * returns last inserted auto_increment id for given $link
     * or $GLOBALS['userlink']
     *
     * @param int $link link type
     */
    public function insertId($link = self::CONNECT_USER): int
    {
        // If the primary key is BIGINT we get an incorrect result
        // (sometimes negative, sometimes positive)
        // and in the present function we don't know if the PK is BIGINT
        // so better play safe and use LAST_INSERT_ID()
        //
        // When no controluser is defined, using mysqli_insert_id($link)
        // does not always return the last insert id due to a mixup with
        // the tracking mechanism, but this works:
        return (int) $this->fetchValue('SELECT LAST_INSERT_ID();', 0, $link);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param int  $link           link type
     * @param bool $get_from_cache whether to retrieve from cache
     *
     * @return int|string
     * @psalm-return int|numeric-string
     */
    public function affectedRows(
        $link = self::CONNECT_USER,
        bool $get_from_cache = true
    ) {
        if (! isset($this->links[$link])) {
            return -1;
        }

        if ($get_from_cache) {
            return $GLOBALS['cached_affected_rows'];
        }

        return $this->extension->affectedRows($this->links[$link]);
    }

    /**
     * returns metainfo for fields in $result
     *
     * @param ResultInterface $result result set identifier
     *
     * @return FieldMetadata[] meta info for fields in $result
     */
    public function getFieldsMeta(ResultInterface $result): array
    {
        $fields = $result->getFieldsMeta();

        if ($this->getLowerCaseNames() === '2') {
            /**
             * Fixup orgtable for lower_case_table_names = 2
             *
             * In this setup MySQL server reports table name lower case
             * but we still need to operate on original case to properly
             * match existing strings
             */
            foreach ($fields as $value) {
                if (
                    strlen($value->orgtable) === 0 ||
                        mb_strtolower($value->orgtable) !== mb_strtolower($value->table)
                ) {
                    continue;
                }

                $value->orgtable = $value->table;
            }
        }

        return $fields;
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param string $str  string to be escaped
     * @param mixed  $link optional database link to use
     *
     * @return string a MySQL escaped string
     */
    public function escapeString(string $str, $link = self::CONNECT_USER)
    {
        if ($this->extension === null || ! isset($this->links[$link])) {
            return $str;
        }

        return $this->extension->escapeString($this->links[$link], $str);
    }

    /**
     * returns properly escaped string for use in MySQL LIKE clauses
     *
     * @param string $str  string to be escaped
     * @param int    $link optional database link to use
     *
     * @return string a MySQL escaped LIKE string
     */
    public function escapeMysqlLikeString(string $str, int $link = self::CONNECT_USER)
    {
        return $this->escapeString(strtr($str, ['\\' => '\\\\', '_' => '\\_', '%' => '\\%']), $link);
    }

    /**
     * Checks if this database server is running on Amazon RDS.
     */
    public function isAmazonRds(): bool
    {
        if (SessionCache::has('is_amazon_rds')) {
            return (bool) SessionCache::get('is_amazon_rds');
        }

        $sql = 'SELECT @@basedir';
        $result = (string) $this->fetchValue($sql);
        $rds = str_starts_with($result, '/rdsdbbin/');
        SessionCache::set('is_amazon_rds', $rds);

        return $rds;
    }

    /**
     * Gets SQL for killing a process.
     *
     * @param int $process Process ID
     */
    public function getKillQuery(int $process): string
    {
        if ($this->isAmazonRds() && $this->isSuperUser()) {
            return 'CALL mysql.rds_kill(' . $process . ');';
        }

        return 'KILL ' . $process . ';';
    }

    /**
     * Get the phpmyadmin database manager
     */
    public function getSystemDatabase(): SystemDatabase
    {
        return new SystemDatabase($this);
    }

    /**
     * Get a table with database name and table name
     *
     * @param string $db_name    DB name
     * @param string $table_name Table name
     */
    public function getTable(string $db_name, string $table_name): Table
    {
        return new Table($table_name, $db_name, $this);
    }

    /**
     * returns collation of given db
     *
     * @param string $db name of db
     *
     * @return string  collation of $db
     */
    public function getDbCollation(string $db): string
    {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            // this is slow with thousands of databases
            $sql = 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA'
                . ' WHERE SCHEMA_NAME = \'' . $this->escapeString($db)
                . '\' LIMIT 1';

            return (string) $this->fetchValue($sql);
        }

        $this->selectDb($db);
        $return = (string) $this->fetchValue('SELECT @@collation_database');
        if ($db !== $GLOBALS['db']) {
            $this->selectDb($GLOBALS['db']);
        }

        return $return;
    }

    /**
     * returns default server collation from show variables
     */
    public function getServerCollation(): string
    {
        return (string) $this->fetchValue('SELECT @@collation_server');
    }

    /**
     * Server version as number
     *
     * @example 80011
     */
    public function getVersion(): int
    {
        return $this->versionInt;
    }

    /**
     * Server version
     */
    public function getVersionString(): string
    {
        return $this->versionString;
    }

    /**
     * Server version comment
     */
    public function getVersionComment(): string
    {
        return $this->versionComment;
    }

    /** Whether connection is MySQL */
    public function isMySql(): bool
    {
        return ! $this->isMariaDb;
    }

    /**
     * Whether connection is MariaDB
     */
    public function isMariaDB(): bool
    {
        return $this->isMariaDb;
    }

    /**
     * Whether connection is PerconaDB
     */
    public function isPercona(): bool
    {
        return $this->isPercona;
    }

    /**
     * Set version
     *
     * @param array $version Database version information
     * @phpstan-param array<array-key, mixed> $version
     */
    public function setVersion(array $version): void
    {
        $this->versionString = $version['@@version'] ?? '';
        $this->versionInt = Utilities::versionToInt($this->versionString);
        $this->versionComment = $version['@@version_comment'] ?? '';

        $this->isMariaDb = stripos($this->versionString, 'mariadb') !== false;
        $this->isPercona = stripos($this->versionComment, 'percona') !== false;
    }

    /**
     * Load correct database driver
     *
     * @param DbiExtension|null $extension Force the use of an alternative extension
     */
    public static function load(?DbiExtension $extension = null): self
    {
        if ($extension !== null) {
            return new self($extension);
        }

        if (! Util::checkDbExtension('mysqli')) {
            $docLink = sprintf(
                __('See %sour documentation%s for more information.'),
                '[doc@faqmysql]',
                '[/doc]'
            );
            Core::warnMissingExtension('mysqli', true, $docLink);
        }

        return new self(new DbiMysqli());
    }

    /**
     * Prepare an SQL statement for execution.
     *
     * @param string $query The query, as a string.
     * @param int    $link  Link type.
     *
     * @return object|false A statement object or false.
     */
    public function prepare(string $query, $link = self::CONNECT_USER)
    {
        return $this->extension->prepare($this->links[$link], $query);
    }
}
