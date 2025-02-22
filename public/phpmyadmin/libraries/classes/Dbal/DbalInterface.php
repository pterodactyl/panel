<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Table;

/**
 * Main interface for database interactions
 */
interface DbalInterface
{
    public const FETCH_NUM = 'NUM';
    public const FETCH_ASSOC = 'ASSOC';

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
        $link = DatabaseInterface::CONNECT_USER,
        int $options = 0,
        bool $cache_affected_rows = true
    ): ResultInterface;

    /**
     * runs a query and returns the result
     *
     * @param string $query               query to run
     * @param mixed  $link                link type
     * @param int    $options             query options
     * @param bool   $cache_affected_rows whether to cache affected row
     *
     * @return mixed
     */
    public function tryQuery(
        string $query,
        $link = DatabaseInterface::CONNECT_USER,
        int $options = 0,
        bool $cache_affected_rows = true
    );

    /**
     * Send multiple SQL queries to the database server and execute the first one
     *
     * @param string $multiQuery multi query statement to execute
     * @param int    $linkIndex  index of the opened database link
     */
    public function tryMultiQuery(
        string $multiQuery = '',
        $linkIndex = DatabaseInterface::CONNECT_USER
    ): bool;

    /**
     * returns array with table names for given db
     *
     * @param string $database name of database
     * @param mixed  $link     mysql link resource|object
     *
     * @return array   tables names
     */
    public function getTables(string $database, $link = DatabaseInterface::CONNECT_USER): array;

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
        $link = DatabaseInterface::CONNECT_USER
    ): array;

    /**
     * Get VIEWs in a particular database
     *
     * @param string $db Database name to look in
     *
     * @return Table[] Set of VIEWs inside the database
     */
    public function getVirtualTables(string $db): array;

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
        $link = DatabaseInterface::CONNECT_USER,
        string $sort_by = 'SCHEMA_NAME',
        string $sort_order = 'ASC',
        int $limit_offset = 0,
        $limit_count = false
    ): array;

    /**
     * returns detailed array with all columns for sql
     *
     * @param string $sql_query    target SQL query to get columns
     * @param array  $view_columns alias for columns
     *
     * @return array
     */
    public function getColumnMapFromSql(string $sql_query, array $view_columns = []): array;

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
        $link = DatabaseInterface::CONNECT_USER
    ): array;

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
        $link = DatabaseInterface::CONNECT_USER
    ): array;

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
        $link = DatabaseInterface::CONNECT_USER
    ): array;

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
        $link = DatabaseInterface::CONNECT_USER
    ): array;

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
        $link = DatabaseInterface::CONNECT_USER
    ): array;

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
        int $type = DatabaseInterface::GETVAR_SESSION,
        $link = DatabaseInterface::CONNECT_USER
    );

    /**
     * Sets new value for a variable if it is different from the current value
     *
     * @param string $var   variable name
     * @param string $value value to set
     * @param int    $link  mysql link resource|object
     */
    public function setVariable(string $var, string $value, $link = DatabaseInterface::CONNECT_USER): bool;

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnect(): void;

    /**
     * Sets collation connection for user link
     *
     * @param string $collation collation to set
     */
    public function setCollation(string $collation): void;

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnectControl(Relation $relation): void;

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
     *                          starting at 0, with 0 being
     *                          default
     * @param int        $link  link type
     *
     * @return string|false|null value of first field in first row from result
     *               or false if not found
     */
    public function fetchValue(
        string $query,
        $field = 0,
        $link = DatabaseInterface::CONNECT_USER
    );

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
     * @param string $type  NUM|ASSOC returned array should either numeric
     *                      associative or both
     * @param int    $link  link type
     * @psalm-param  self::FETCH_NUM|self::FETCH_ASSOC $type
     */
    public function fetchSingleRow(
        string $query,
        string $type = DbalInterface::FETCH_ASSOC,
        $link = DatabaseInterface::CONNECT_USER
    ): ?array;

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
     * @param string           $query query to execute
     * @param string|int|array $key   field-name or offset
     *                                used as key for
     *                                array or array of
     *                                those
     * @param string|int       $value value-name or offset
     *                                used as value for
     *                                array
     * @param int              $link  link type
     *
     * @return array resultrows or values indexed by $key
     */
    public function fetchResult(
        string $query,
        $key = null,
        $value = null,
        $link = DatabaseInterface::CONNECT_USER
    ): array;

    /**
     * Get supported SQL compatibility modes
     *
     * @return array supported SQL compatibility modes
     */
    public function getCompatibilities(): array;

    /**
     * returns warnings for last query
     *
     * @param int $link link type
     *
     * @return array warnings
     */
    public function getWarnings($link = DatabaseInterface::CONNECT_USER): array;

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
        $link = DatabaseInterface::CONNECT_USER
    ): array;

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
        $link = DatabaseInterface::CONNECT_USER
    ): ?string;

    /**
     * returns details about the PROCEDUREs or FUNCTIONs for a specific database
     * or details about a specific routine
     *
     * @param string      $db    db name
     * @param string|null $which PROCEDURE | FUNCTION or null for both
     * @param string      $name  name of the routine (to fetch a specific routine)
     *
     * @return array information about ROCEDUREs or FUNCTIONs
     */
    public function getRoutines(string $db, ?string $which = null, string $name = ''): array;

    /**
     * returns details about the EVENTs for a specific database
     *
     * @param string $db   db name
     * @param string $name event name
     *
     * @return array information about EVENTs
     */
    public function getEvents(string $db, string $name = ''): array;

    /**
     * returns details about the TRIGGERs for a specific table or database
     *
     * @param string $db        db name
     * @param string $table     table name
     * @param string $delimiter the delimiter to use (may be empty)
     *
     * @return array information about triggers (may be empty)
     */
    public function getTriggers(string $db, string $table = '', string $delimiter = '//'): array;

    /**
     * gets the current user with host
     *
     * @return string the current user i.e. user@host
     */
    public function getCurrentUser(): string;

    /**
     * Checks if current user is superuser
     */
    public function isSuperUser(): bool;

    public function isGrantUser(): bool;

    public function isCreateUser(): bool;

    public function isConnected(): bool;

    /**
     * Get the current user and host
     *
     * @return array array of username and hostname
     */
    public function getCurrentUserAndHost(): array;

    /**
     * Returns value for lower_case_table_names variable
     *
     * @return string
     */
    public function getLowerCaseNames();

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
    public function connect(int $mode, ?array $server = null, ?int $target = null);

    /**
     * selects given database
     *
     * @param string|DatabaseName $dbname database name to select
     * @param int                 $link   link type
     */
    public function selectDb($dbname, $link = DatabaseInterface::CONNECT_USER): bool;

    /**
     * Check if there are any more query results from a multi query
     *
     * @param int $link link type
     */
    public function moreResults($link = DatabaseInterface::CONNECT_USER): bool;

    /**
     * Prepare next result from multi_query
     *
     * @param int $link link type
     */
    public function nextResult($link = DatabaseInterface::CONNECT_USER): bool;

    /**
     * Store the result returned from multi query
     *
     * @param int $link link type
     *
     * @return mixed false when empty results / result set when not empty
     */
    public function storeResult($link = DatabaseInterface::CONNECT_USER);

    /**
     * Returns a string representing the type of connection used
     *
     * @param int $link link type
     *
     * @return string|bool type of connection used
     */
    public function getHostInfo($link = DatabaseInterface::CONNECT_USER);

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param int $link link type
     *
     * @return int|bool version of the MySQL protocol used
     */
    public function getProtoInfo($link = DatabaseInterface::CONNECT_USER);

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo(): string;

    /**
     * Returns last error message or an empty string if no errors occurred.
     *
     * @param int $link link type
     */
    public function getError($link = DatabaseInterface::CONNECT_USER): string;

    /**
     * returns the number of rows returned by last query
     * used with tryQuery as it accepts false
     *
     * @param string $query query to run
     *
     * @return string|int
     * @psalm-return int|numeric-string
     */
    public function queryAndGetNumRows(string $query);

    /**
     * returns last inserted auto_increment id for given $link
     * or $GLOBALS['userlink']
     *
     * @param int $link link type
     *
     * @return int
     */
    public function insertId($link = DatabaseInterface::CONNECT_USER);

    /**
     * returns the number of rows affected by last query
     *
     * @param int  $link           link type
     * @param bool $get_from_cache whether to retrieve from cache
     *
     * @return int|string
     * @psalm-return int|numeric-string
     */
    public function affectedRows($link = DatabaseInterface::CONNECT_USER, bool $get_from_cache = true);

    /**
     * returns metainfo for fields in $result
     *
     * @param ResultInterface $result result set identifier
     *
     * @return FieldMetadata[] meta info for fields in $result
     */
    public function getFieldsMeta(ResultInterface $result): array;

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param string $str  string to be escaped
     * @param mixed  $link optional database link to use
     *
     * @return string a MySQL escaped string
     */
    public function escapeString(string $str, $link = DatabaseInterface::CONNECT_USER);

    /**
     * returns properly escaped string for use in MySQL LIKE clauses
     *
     * @param string $str  string to be escaped
     * @param int    $link optional database link to use
     *
     * @return string a MySQL escaped LIKE string
     */
    public function escapeMysqlLikeString(string $str, int $link = DatabaseInterface::CONNECT_USER);

    /**
     * Checks if this database server is running on Amazon RDS.
     */
    public function isAmazonRds(): bool;

    /**
     * Gets SQL for killing a process.
     *
     * @param int $process Process ID
     */
    public function getKillQuery(int $process): string;

    /**
     * Get the phpmyadmin database manager
     */
    public function getSystemDatabase(): SystemDatabase;

    /**
     * Get a table with database name and table name
     *
     * @param string $db_name    DB name
     * @param string $table_name Table name
     */
    public function getTable(string $db_name, string $table_name): Table;

    /**
     * returns collation of given db
     *
     * @param string $db name of db
     *
     * @return string  collation of $db
     */
    public function getDbCollation(string $db): string;

    /**
     * returns default server collation from show variables
     */
    public function getServerCollation(): string;

    /**
     * Server version as number
     */
    public function getVersion(): int;

    /**
     * Server version
     */
    public function getVersionString(): string;

    /**
     * Server version comment
     */
    public function getVersionComment(): string;

    /** Whether connection is MySQL */
    public function isMySql(): bool;

    /**
     * Whether connection is MariaDB
     */
    public function isMariaDB(): bool;

    /**
     * Whether connection is Percona
     */
    public function isPercona(): bool;

    /**
     * Prepare an SQL statement for execution.
     *
     * @param string $query The query, as a string.
     * @param int    $link  Link type.
     *
     * @return object|false A statement object or false.
     */
    public function prepare(string $query, $link = DatabaseInterface::CONNECT_USER);
}
