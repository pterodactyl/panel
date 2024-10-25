<?php
/**
 * Tracking changes on databases, tables and views
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\RenameStatement;
use PhpMyAdmin\SqlParser\Statements\TruncateStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;

use function array_values;
use function count;
use function explode;
use function intval;
use function is_array;
use function mb_strpos;
use function mb_strstr;
use function mb_substr;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function serialize;
use function sprintf;
use function str_replace;
use function strtotime;
use function substr;
use function trim;

/**
 * This class tracks changes on databases, tables and views.
 *
 * @todo use stristr instead of strstr
 */
class Tracker
{
    public const TRACKER_ENABLED_CACHE_KEY = 'phpmyadmin.tracker.enabled';

    /**
     * Cache to avoid quering tracking status multiple times.
     *
     * @var array
     */
    protected static $trackingCache = [];

    /**
     * Actually enables tracking. This needs to be done after all
     * underlaying code is initialized.
     */
    public static function enable(): void
    {
        Cache::set(self::TRACKER_ENABLED_CACHE_KEY, true);
    }

    /**
     * Gets the on/off value of the Tracker module, starts initialization.
     *
     * @static
     */
    public static function isActive(): bool
    {
        global $dbi;

        $trackingEnabled = Cache::get(self::TRACKER_ENABLED_CACHE_KEY, false);
        if (! $trackingEnabled) {
            return false;
        }

        /**
         * We need to avoid attempt to track any queries from {@link Relation::getRelationParameters()}
         */
        Cache::set(self::TRACKER_ENABLED_CACHE_KEY, false);
        $relation = new Relation($dbi);
        $relationParameters = $relation->getRelationParameters();
        /* Restore original state */
        Cache::set(self::TRACKER_ENABLED_CACHE_KEY, true);

        return $relationParameters->trackingFeature !== null;
    }

    /**
     * Parses the name of a table from a SQL statement substring.
     *
     * @param string $string part of SQL statement
     *
     * @return string the name of table
     *
     * @static
     */
    protected static function getTableName($string)
    {
        if (mb_strstr($string, '.')) {
            $temp = explode('.', $string);
            $tableName = $temp[1];
        } else {
            $tableName = $string;
        }

        $str = explode("\n", $tableName);
        $tableName = $str[0];

        $tableName = str_replace([';', '`'], '', $tableName);
        $tableName = trim($tableName);

        return $tableName;
    }

    /**
     * Gets the tracking status of a table, is it active or disabled ?
     *
     * @param string $dbName    name of database
     * @param string $tableName name of table
     *
     * @static
     */
    public static function isTracked($dbName, $tableName): bool
    {
        global $dbi;

        $trackingEnabled = Cache::get(self::TRACKER_ENABLED_CACHE_KEY, false);
        if (! $trackingEnabled) {
            return false;
        }

        if (isset(self::$trackingCache[$dbName][$tableName])) {
            return self::$trackingCache[$dbName][$tableName];
        }

        /**
         * We need to avoid attempt to track any queries from {@link Relation::getRelationParameters()}
         */
        Cache::set(self::TRACKER_ENABLED_CACHE_KEY, false);
        $relation = new Relation($dbi);
        $trackingFeature = $relation->getRelationParameters()->trackingFeature;
        /* Restore original state */
        Cache::set(self::TRACKER_ENABLED_CACHE_KEY, true);
        if ($trackingFeature === null) {
            return false;
        }

        $sqlQuery = sprintf(
            'SELECT tracking_active FROM %s.%s WHERE db_name = \'%s\' AND table_name = \'%s\''
                . ' ORDER BY version DESC LIMIT 1',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $dbi->escapeString($dbName),
            $dbi->escapeString($tableName)
        );

        $result = $dbi->fetchValue($sqlQuery, 0, DatabaseInterface::CONNECT_CONTROL) == 1;

        self::$trackingCache[$dbName][$tableName] = $result;

        return $result;
    }

    /**
     * Returns the comment line for the log.
     *
     * @return string Comment, contains date and username
     */
    public static function getLogComment()
    {
        $date = Util::date('Y-m-d H:i:s');
        $user = preg_replace('/\s+/', ' ', $GLOBALS['cfg']['Server']['user']);

        return '# log ' . $date . ' ' . $user . "\n";
    }

    /**
     * Creates tracking version of a table / view
     * (in other words: create a job to track future changes on the table).
     *
     * @param string $dbName      name of database
     * @param string $tableName   name of table
     * @param string $version     version
     * @param string $trackingSet set of tracking statements
     * @param bool   $isView      if table is a view
     *
     * @static
     */
    public static function createVersion(
        $dbName,
        $tableName,
        $version,
        $trackingSet = '',
        bool $isView = false
    ): bool {
        global $sql_backquotes, $export_type, $dbi;

        $relation = new Relation($dbi);

        if ($trackingSet == '') {
            $trackingSet = $GLOBALS['cfg']['Server']['tracking_default_statements'];
        }

        $exportSqlPlugin = Plugins::getPlugin('export', 'sql', [
            'export_type' => (string) $export_type,
            'single_table' => false,
        ]);
        if (! $exportSqlPlugin instanceof ExportSql) {
            return false;
        }

        $sql_backquotes = true;

        $date = Util::date('Y-m-d H:i:s');

        // Get data definition snapshot of table

        $columns = $dbi->getColumns($dbName, $tableName, true);
        // int indices to reduce size
        $columns = array_values($columns);
        // remove Privileges to reduce size
        for ($i = 0, $nb = count($columns); $i < $nb; $i++) {
            unset($columns[$i]['Privileges']);
        }

        $indexes = $dbi->getTableIndexes($dbName, $tableName);

        $snapshot = [
            'COLUMNS' => $columns,
            'INDEXES' => $indexes,
        ];
        $snapshot = serialize($snapshot);

        // Get DROP TABLE / DROP VIEW and CREATE TABLE SQL statements
        $sql_backquotes = true;

        $createSql = '';

        if ($GLOBALS['cfg']['Server']['tracking_add_drop_table'] == true && $isView === false) {
            $createSql .= self::getLogComment()
                . 'DROP TABLE IF EXISTS ' . Util::backquote($tableName) . ";\n";
        }

        if ($GLOBALS['cfg']['Server']['tracking_add_drop_view'] == true && $isView === true) {
            $createSql .= self::getLogComment()
                . 'DROP VIEW IF EXISTS ' . Util::backquote($tableName) . ";\n";
        }

        $createSql .= self::getLogComment() .
            $exportSqlPlugin->getTableDef($dbName, $tableName, "\n", '');

        // Save version
        $trackingFeature = $relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        $sqlQuery = sprintf(
            '/*NOTRACK*/' . "\n" . 'INSERT INTO %s.%s (db_name, table_name, version,'
                . ' date_created, date_updated, schema_snapshot, schema_sql, data_sql, tracking)'
                . ' values (\'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\')',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $dbi->escapeString($dbName),
            $dbi->escapeString($tableName),
            $dbi->escapeString($version),
            $dbi->escapeString($date),
            $dbi->escapeString($date),
            $dbi->escapeString($snapshot),
            $dbi->escapeString($createSql),
            $dbi->escapeString("\n"),
            $dbi->escapeString($trackingSet)
        );

        $dbi->queryAsControlUser($sqlQuery);

        // Deactivate previous version
        return self::deactivateTracking($dbName, $tableName, (int) $version - 1);
    }

    /**
     * Removes all tracking data for a table or a version of a table
     *
     * @param string $dbName    name of database
     * @param string $tableName name of table
     * @param string $version   version
     */
    public static function deleteTracking($dbName, $tableName, $version = ''): bool
    {
        global $dbi;

        $relation = new Relation($dbi);
        $trackingFeature = $relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        $sqlQuery = sprintf(
            '/*NOTRACK*/' . "\n" . 'DELETE FROM %s.%s WHERE `db_name` = \'%s\' AND `table_name` = \'%s\'',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $dbi->escapeString($dbName),
            $dbi->escapeString($tableName)
        );
        if ($version) {
            $sqlQuery .= " AND `version` = '" . $dbi->escapeString($version) . "'";
        }

        return (bool) $dbi->queryAsControlUser($sqlQuery);
    }

    /**
     * Creates tracking version of a database
     * (in other words: create a job to track future changes on the database).
     *
     * @param string $dbName      name of database
     * @param string $version     version
     * @param string $query       query
     * @param string $trackingSet set of tracking statements
     */
    public static function createDatabaseVersion(
        $dbName,
        $version,
        $query,
        $trackingSet = 'CREATE DATABASE,ALTER DATABASE,DROP DATABASE'
    ): bool {
        global $dbi;

        $relation = new Relation($dbi);

        $date = Util::date('Y-m-d H:i:s');

        if ($trackingSet == '') {
            $trackingSet = $GLOBALS['cfg']['Server']['tracking_default_statements'];
        }

        $createSql = '';

        if ($GLOBALS['cfg']['Server']['tracking_add_drop_database'] == true) {
            $createSql .= self::getLogComment() . 'DROP DATABASE IF EXISTS ' . Util::backquote($dbName) . ";\n";
        }

        $createSql .= self::getLogComment() . $query;

        $trackingFeature = $relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        // Save version
        $sqlQuery = sprintf(
            '/*NOTRACK*/' . "\n" . 'INSERT INTO %s.%s (db_name, table_name, version,'
                . ' date_created, date_updated, schema_snapshot, schema_sql, data_sql, tracking)'
                . ' values (\'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\')',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $dbi->escapeString($dbName),
            $dbi->escapeString(''),
            $dbi->escapeString($version),
            $dbi->escapeString($date),
            $dbi->escapeString($date),
            $dbi->escapeString(''),
            $dbi->escapeString($createSql),
            $dbi->escapeString("\n"),
            $dbi->escapeString($trackingSet)
        );

        return (bool) $dbi->queryAsControlUser($sqlQuery);
    }

    /**
     * Changes tracking of a table.
     *
     * @param string $dbName    name of database
     * @param string $tableName name of table
     * @param string $version   version
     * @param int    $newState  the new state of tracking
     */
    private static function changeTracking(
        $dbName,
        $tableName,
        $version,
        $newState
    ): bool {
        global $dbi;

        $relation = new Relation($dbi);
        $trackingFeature = $relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        $sqlQuery = sprintf(
            'UPDATE %s.%s SET `tracking_active` = \'%d\''
                . ' WHERE `db_name` = \'%s\' AND `table_name` = \'%s\' AND `version` = \'%s\'',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $newState,
            $dbi->escapeString($dbName),
            $dbi->escapeString($tableName),
            $dbi->escapeString((string) $version)
        );

        return (bool) $dbi->queryAsControlUser($sqlQuery);
    }

    /**
     * Changes tracking data of a table.
     *
     * @param string       $dbName    name of database
     * @param string       $tableName name of table
     * @param string       $version   version
     * @param string       $type      type of data(DDL || DML)
     * @param string|array $newData   the new tracking data
     *
     * @static
     */
    public static function changeTrackingData(
        $dbName,
        $tableName,
        $version,
        $type,
        $newData
    ): bool {
        global $dbi;

        $relation = new Relation($dbi);

        if ($type === 'DDL') {
            $saveTo = 'schema_sql';
        } elseif ($type === 'DML') {
            $saveTo = 'data_sql';
        } else {
            return false;
        }

        $date = Util::date('Y-m-d H:i:s');

        $newDataProcessed = '';
        if (is_array($newData)) {
            foreach ($newData as $data) {
                $newDataProcessed .= '# log ' . $date . ' ' . $data['username']
                    . $dbi->escapeString($data['statement']) . "\n";
            }
        } else {
            $newDataProcessed = $newData;
        }

        $trackingFeature = $relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        $sqlQuery = sprintf(
            'UPDATE %s.%s SET `%s` = \'%s\' WHERE `db_name` = \'%s\' AND `table_name` = \'%s\' AND `version` = \'%s\'',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $saveTo,
            $newDataProcessed,
            $dbi->escapeString($dbName),
            $dbi->escapeString($tableName),
            $dbi->escapeString($version)
        );

        $result = $dbi->queryAsControlUser($sqlQuery);

        return (bool) $result;
    }

    /**
     * Activates tracking of a table.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version
     */
    public static function activateTracking($dbname, $tablename, $version): bool
    {
        return self::changeTracking($dbname, $tablename, $version, 1);
    }

    /**
     * Deactivates tracking of a table.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version
     */
    public static function deactivateTracking($dbname, $tablename, $version): bool
    {
        return self::changeTracking($dbname, $tablename, $version, 0);
    }

    /**
     * Gets the newest version of a tracking job
     * (in other words: gets the HEAD version).
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $statement tracked statement
     *
     * @return int (-1 if no version exists | >  0 if a version exists)
     *
     * @static
     */
    public static function getVersion(string $dbname, string $tablename, ?string $statement = null)
    {
        global $dbi;

        $relation = new Relation($dbi);
        $trackingFeature = $relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return -1;
        }

        $sqlQuery = sprintf(
            'SELECT MAX(version) FROM %s.%s WHERE `db_name` = \'%s\' AND `table_name` = \'%s\'',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $dbi->escapeString($dbname),
            $dbi->escapeString($tablename)
        );

        if ($statement != '') {
            $sqlQuery .= " AND FIND_IN_SET('" . $statement . "',tracking) > 0";
        }

        $result = $dbi->tryQueryAsControlUser($sqlQuery);

        if ($result === false) {
            return -1;
        }

        $row = $result->fetchRow();

        return intval($row[0] ?? -1);
    }

    /**
     * Gets the record of a tracking job.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version number
     *
     * @return mixed record DDM log, DDL log, structure snapshot, tracked
     *         statements.
     *
     * @static
     */
    public static function getTrackedData($dbname, $tablename, $version)
    {
        global $dbi;

        $relation = new Relation($dbi);
        $trackingFeature = $relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return [];
        }

        $sqlQuery = sprintf(
            'SELECT * FROM %s.%s WHERE `db_name` = \'%s\'',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $dbi->escapeString($dbname)
        );
        if (! empty($tablename)) {
            $sqlQuery .= " AND `table_name` = '"
                . $dbi->escapeString($tablename) . "' ";
        }

        $sqlQuery .= " AND `version` = '" . $dbi->escapeString($version)
            . "' ORDER BY `version` DESC LIMIT 1";

        $mixed = $dbi->queryAsControlUser($sqlQuery)->fetchAssoc();

        // PHP 7.4 fix for accessing array offset on null
        if ($mixed === []) {
            $mixed = [
                'schema_sql' => null,
                'data_sql' => null,
                'tracking' => null,
                'schema_snapshot' => null,
            ];
        }

        // Parse log
        $logSchemaEntries = explode('# log ', (string) $mixed['schema_sql']);
        $logDataEntries = explode('# log ', (string) $mixed['data_sql']);

        $ddlDateFrom = $date = Util::date('Y-m-d H:i:s');

        $ddlog = [];
        $firstIteration = true;

        // Iterate tracked data definition statements
        // For each log entry we want to get date, username and statement
        foreach ($logSchemaEntries as $logEntry) {
            if (trim($logEntry) == '') {
                continue;
            }

            $date = mb_substr($logEntry, 0, 19);
            $username = mb_substr(
                $logEntry,
                20,
                mb_strpos($logEntry, "\n") - 20
            );
            if ($firstIteration) {
                $ddlDateFrom = $date;
                $firstIteration = false;
            }

            $statement = rtrim((string) mb_strstr($logEntry, "\n"));

            $ddlog[] = [
                'date' => $date,
                'username' => $username,
                'statement' => $statement,
            ];
        }

        $dateFrom = $ddlDateFrom;
        $ddlDateTo = $date;

        $dmlDateFrom = $dateFrom;

        $dmlog = [];
        $firstIteration = true;

        // Iterate tracked data manipulation statements
        // For each log entry we want to get date, username and statement
        foreach ($logDataEntries as $logEntry) {
            if (trim($logEntry) == '') {
                continue;
            }

            $date = mb_substr($logEntry, 0, 19);
            $username = mb_substr(
                $logEntry,
                20,
                mb_strpos($logEntry, "\n") - 20
            );
            if ($firstIteration) {
                $dmlDateFrom = $date;
                $firstIteration = false;
            }

            $statement = rtrim((string) mb_strstr($logEntry, "\n"));

            $dmlog[] = [
                'date' => $date,
                'username' => $username,
                'statement' => $statement,
            ];
        }

        $dmlDateTo = $date;

        // Define begin and end of date range for both logs
        $data = [];
        if (strtotime($ddlDateFrom) <= strtotime($dmlDateFrom)) {
            $data['date_from'] = $ddlDateFrom;
        } else {
            $data['date_from'] = $dmlDateFrom;
        }

        if (strtotime($ddlDateTo) >= strtotime($dmlDateTo)) {
            $data['date_to'] = $ddlDateTo;
        } else {
            $data['date_to'] = $dmlDateTo;
        }

        $data['ddlog'] = $ddlog;
        $data['dmlog'] = $dmlog;
        $data['tracking'] = $mixed['tracking'];
        $data['schema_snapshot'] = $mixed['schema_snapshot'];

        return $data;
    }

    /**
     * Parses a query. Gets
     *  - statement identifier (UPDATE, ALTER TABLE, ...)
     *  - type of statement, is it part of DDL or DML ?
     *  - tablename
     *
     * @param string $query query
     *
     * @return array containing identifier, type and tablename.
     *
     * @static
     * @todo: using PMA SQL Parser when possible
     * @todo: support multi-table/view drops
     */
    public static function parseQuery($query): array
    {
        // Usage of PMA_SQP does not work here
        //
        // require_once("libraries/sqlparser.lib.php");
        // $parsed_sql = PMA_SQP_parse($query);
        // $sql_info = PMA_SQP_analyze($parsed_sql);

        $parser = new Parser($query);

        $tokens = $parser->list->tokens;

        // Parse USE statement, need it for SQL dump imports
        if ($tokens[0]->value === 'USE') {
            $GLOBALS['db'] = $tokens[2]->value;
        }

        $result = [];

        if (! empty($parser->statements)) {
            $statement = $parser->statements[0];
            $options = isset($statement->options) ? $statement->options->options : null;

            /*
             * DDL statements
             */
            $result['type'] = 'DDL';

            // Parse CREATE statement
            if ($statement instanceof CreateStatement) {
                if (empty($options) || ! isset($options[6])) {
                    return $result;
                }

                if ($options[6] === 'VIEW' || $options[6] === 'TABLE') {
                    $result['identifier'] = 'CREATE ' . $options[6];
                    $result['tablename'] = $statement->name !== null ? $statement->name->table : null;
                } elseif ($options[6] === 'DATABASE') {
                    $result['identifier'] = 'CREATE DATABASE';
                    $result['tablename'] = '';

                    // In case of CREATE DATABASE, database field of the CreateStatement is the name of the database
                    $GLOBALS['db'] = $statement->name !== null ? $statement->name->database : null;
                } elseif (
                    $options[6] === 'INDEX'
                          || $options[6] === 'UNIQUE INDEX'
                          || $options[6] === 'FULLTEXT INDEX'
                          || $options[6] === 'SPATIAL INDEX'
                ) {
                    $result['identifier'] = 'CREATE INDEX';

                    // In case of CREATE INDEX, we have to get the table name from body of the statement
                    $result['tablename'] = $statement->body[3]->value === '.' ? $statement->body[4]->value
                                                                              : $statement->body[2]->value;
                }
            } elseif ($statement instanceof AlterStatement) { // Parse ALTER statement
                if (empty($options) || ! isset($options[3])) {
                    return $result;
                }

                if ($options[3] === 'VIEW' || $options[3] === 'TABLE') {
                    $result['identifier'] = 'ALTER ' . $options[3];
                    $result['tablename'] = $statement->table->table;
                } elseif ($options[3] === 'DATABASE') {
                    $result['identifier'] = 'ALTER DATABASE';
                    $result['tablename'] = '';

                    $GLOBALS['db'] = $statement->table->table;
                }
            } elseif ($statement instanceof DropStatement) { // Parse DROP statement
                if (empty($options) || ! isset($options[1])) {
                    return $result;
                }

                if ($options[1] === 'VIEW' || $options[1] === 'TABLE') {
                    $result['identifier'] = 'DROP ' . $options[1];
                    $result['tablename'] = $statement->fields[0]->table;
                } elseif ($options[1] === 'DATABASE') {
                    $result['identifier'] = 'DROP DATABASE';
                    $result['tablename'] = '';

                    $GLOBALS['db'] = $statement->fields[0]->table;
                } elseif ($options[1] === 'INDEX') {
                    $result['identifier'] = 'DROP INDEX';
                    $result['tablename'] = $statement->table->table;
                }
            } elseif ($statement instanceof RenameStatement) { // Parse RENAME statement
                $result['identifier'] = 'RENAME TABLE';
                $result['tablename'] = $statement->renames[0]->old->table;
                $result['tablename_after_rename'] = $statement->renames[0]->new->table;
            }

            if (isset($result['identifier'])) {
                return $result;
            }

            /*
             * DML statements
             */
            $result['type'] = 'DML';

            // Parse UPDATE statement
            if ($statement instanceof UpdateStatement) {
                $result['identifier'] = 'UPDATE';
                $result['tablename'] = $statement->tables[0]->table;
            }

            // Parse INSERT INTO statement
            if ($statement instanceof InsertStatement) {
                $result['identifier'] = 'INSERT';
                $result['tablename'] = $statement->into->dest->table;
            }

            // Parse DELETE statement
            if ($statement instanceof DeleteStatement) {
                $result['identifier'] = 'DELETE';
                $result['tablename'] = $statement->from[0]->table;
            }

            // Parse TRUNCATE statement
            if ($statement instanceof TruncateStatement) {
                $result['identifier'] = 'TRUNCATE';
                $result['tablename'] = $statement->table->table;
            }
        }

        return $result;
    }

    /**
     * Analyzes a given SQL statement and saves tracking data.
     *
     * @param string $query a SQL query
     *
     * @static
     */
    public static function handleQuery($query): void
    {
        global $dbi;

        $relation = new Relation($dbi);

        // If query is marked as untouchable, leave
        if (mb_strstr($query, '/*NOTRACK*/')) {
            return;
        }

        if (! (substr($query, -1) === ';')) {
            $query .= ";\n";
        }

        // Get database name
        $dbname = trim($GLOBALS['db'] ?? '', '`');
        // $dbname can be empty, for example when coming from Synchronize
        // and this is a query for the remote server
        if (empty($dbname)) {
            return;
        }

        // Get some information about query
        $result = self::parseQuery($query);

        // If we found a valid statement
        if (! isset($result['identifier'])) {
            return;
        }

        // The table name was not found, see issue: #16837 as an example
        // Also checks if the value is not null
        if (! isset($result['tablename'])) {
            return;
        }

        $version = self::getVersion($dbname, $result['tablename'], $result['identifier']);

        // If version not exists and auto-creation is enabled
        if ($GLOBALS['cfg']['Server']['tracking_version_auto_create'] == true && $version == -1) {
            // Create the version

            switch ($result['identifier']) {
                case 'CREATE TABLE':
                    self::createVersion($dbname, $result['tablename'], '1');
                    break;
                case 'CREATE VIEW':
                    self::createVersion($dbname, $result['tablename'], '1', '', true);
                    break;
                case 'CREATE DATABASE':
                    self::createDatabaseVersion($dbname, '1', $query);
                    break;
            }
        }

        // If version exists
        if ($version == -1) {
            return;
        }

        if (! self::isTracked($dbname, $result['tablename'])) {
            return;
        }

        if ($result['type'] === 'DDL') {
            $saveTo = 'schema_sql';
        } elseif ($result['type'] === 'DML') {
            $saveTo = 'data_sql';
        } else {
            $saveTo = '';
        }

        $date = Util::date('Y-m-d H:i:s');

        // Cut off `dbname`. from query
        $query = preg_replace(
            '/`' . preg_quote($dbname, '/') . '`\s?\./',
            '',
            $query
        );

        // Add log information
        $query = self::getLogComment() . $query;

        $trackingFeature = $relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return;
        }

        // Mark it as untouchable
        $sqlQuery = sprintf(
            '/*NOTRACK*/' . "\n" . 'UPDATE %s.%s SET %s = CONCAT(%s, \'' . "\n" . '%s\'), `date_updated` = \'%s\'',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            Util::backquote($saveTo),
            Util::backquote($saveTo),
            $dbi->escapeString($query),
            $date
        );

        // If table was renamed we have to change
        // the tablename attribute in pma_tracking too
        if ($result['identifier'] === 'RENAME TABLE') {
            $sqlQuery .= ', `table_name` = \''
                . $dbi->escapeString($result['tablename_after_rename'])
                . '\' ';
        }

        // Save the tracking information only for
        //     1. the database
        //     2. the table / view
        //     3. the statements
        // we want to track
        $sqlQuery .= " WHERE FIND_IN_SET('" . $result['identifier'] . "',tracking) > 0" .
        " AND `db_name` = '" . $dbi->escapeString($dbname ?? '') . "' " .
        " AND `table_name` = '"
        . $dbi->escapeString($result['tablename']) . "' " .
        " AND `version` = '" . $dbi->escapeString((string) $version) . "' ";

        $dbi->queryAsControlUser($sqlQuery);
    }
}
