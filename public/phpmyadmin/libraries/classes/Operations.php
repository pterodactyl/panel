<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Plugins\Export\ExportSql;

use function __;
use function array_merge;
use function count;
use function explode;
use function is_scalar;
use function is_string;
use function mb_strtolower;
use function str_replace;
use function strlen;
use function strtolower;
use function urldecode;

/**
 * Set of functions with the operations section in phpMyAdmin
 */
class Operations
{
    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Relation          $relation Relation object
     */
    public function __construct(DatabaseInterface $dbi, Relation $relation)
    {
        $this->dbi = $dbi;
        $this->relation = $relation;
    }

    /**
     * Run the Procedure definitions and function definitions
     *
     * to avoid selecting alternatively the current and new db
     * we would need to modify the CREATE definitions to qualify
     * the db name
     *
     * @param string $db database name
     */
    public function runProcedureAndFunctionDefinitions($db): void
    {
        $procedure_names = $this->dbi->getProceduresOrFunctions($db, 'PROCEDURE');
        if ($procedure_names) {
            foreach ($procedure_names as $procedure_name) {
                $this->dbi->selectDb($db);
                $tmp_query = $this->dbi->getDefinition($db, 'PROCEDURE', $procedure_name);
                if ($tmp_query === null) {
                    continue;
                }

                // collect for later display
                $GLOBALS['sql_query'] .= "\n" . $tmp_query;
                $this->dbi->selectDb($_POST['newname']);
                $this->dbi->query($tmp_query);
            }
        }

        $function_names = $this->dbi->getProceduresOrFunctions($db, 'FUNCTION');
        if (! $function_names) {
            return;
        }

        foreach ($function_names as $function_name) {
            $this->dbi->selectDb($db);
            $tmp_query = $this->dbi->getDefinition($db, 'FUNCTION', $function_name);
            if ($tmp_query === null) {
                continue;
            }

            // collect for later display
            $GLOBALS['sql_query'] .= "\n" . $tmp_query;
            $this->dbi->selectDb($_POST['newname']);
            $this->dbi->query($tmp_query);
        }
    }

    /**
     * Create database before copy
     */
    public function createDbBeforeCopy(): void
    {
        $local_query = 'CREATE DATABASE IF NOT EXISTS '
            . Util::backquote($_POST['newname']);
        if (isset($_POST['db_collation'])) {
            $local_query .= ' DEFAULT'
                . Util::getCharsetQueryPart($_POST['db_collation'] ?? '');
        }

        $local_query .= ';';
        $GLOBALS['sql_query'] .= $local_query;

        // save the original db name because Tracker.php which
        // may be called under $this->dbi->query() changes $GLOBALS['db']
        // for some statements, one of which being CREATE DATABASE
        $original_db = $GLOBALS['db'];
        $this->dbi->query($local_query);
        $GLOBALS['db'] = $original_db;

        // Set the SQL mode to NO_AUTO_VALUE_ON_ZERO to prevent MySQL from creating
        // export statements it cannot import
        $sql_set_mode = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
        $this->dbi->query($sql_set_mode);

        // rebuild the database list because Table::moveCopy
        // checks in this list if the target db exists
        $GLOBALS['dblist']->databases->build();
    }

    /**
     * Get views as an array and create SQL view stand-in
     *
     * @param string[]  $tables            array of all tables in given db or dbs
     * @param ExportSql $export_sql_plugin export plugin instance
     * @param string    $db                database name
     *
     * @return array
     */
    public function getViewsAndCreateSqlViewStandIn(
        array $tables,
        $export_sql_plugin,
        $db
    ) {
        $views = [];
        foreach ($tables as $table) {
            // to be able to rename a db containing views,
            // first all the views are collected and a stand-in is created
            // the real views are created after the tables
            if (! $this->dbi->getTable($db, $table)->isView()) {
                continue;
            }

            // If view exists, and 'add drop view' is selected: Drop it!
            if ($_POST['what'] !== 'nocopy' && isset($_POST['drop_if_exists']) && $_POST['drop_if_exists'] === 'true') {
                $drop_query = 'DROP VIEW IF EXISTS '
                    . Util::backquote($_POST['newname']) . '.'
                    . Util::backquote($table);
                $this->dbi->query($drop_query);

                $GLOBALS['sql_query'] .= "\n" . $drop_query . ';';
            }

            $views[] = $table;
            // Create stand-in definition to resolve view dependencies
            $sql_view_standin = $export_sql_plugin->getTableDefStandIn($db, $table, "\n");
            $this->dbi->selectDb($_POST['newname']);
            $this->dbi->query($sql_view_standin);
            $GLOBALS['sql_query'] .= "\n" . $sql_view_standin;
        }

        return $views;
    }

    /**
     * Get sql query for copy/rename table and boolean for whether copy/rename or not
     *
     * @param string[] $tables array of all tables in given db or dbs
     * @param bool     $move   whether database name is empty or not
     * @param string   $db     database name
     *
     * @return array SQL queries for the constraints
     */
    public function copyTables(array $tables, $move, $db)
    {
        $sqlContraints = [];
        foreach ($tables as $table) {
            // skip the views; we have created stand-in definitions
            if ($this->dbi->getTable($db, $table)->isView()) {
                continue;
            }

            // value of $what for this table only
            $this_what = $_POST['what'];

            // do not copy the data from a Merge table
            // note: on the calling FORM, 'data' means 'structure and data'
            if ($this->dbi->getTable($db, $table)->isMerge()) {
                if ($this_what === 'data') {
                    $this_what = 'structure';
                }

                if ($this_what === 'dataonly') {
                    $this_what = 'nocopy';
                }
            }

            if ($this_what === 'nocopy') {
                continue;
            }

            // keep the triggers from the original db+table
            // (third param is empty because delimiters are only intended
            //  for importing via the mysql client or our Import feature)
            $triggers = $this->dbi->getTriggers($db, $table, '');

            if (
                ! Table::moveCopy(
                    $db,
                    $table,
                    $_POST['newname'],
                    $table,
                    ($this_what ?? 'data'),
                    $move,
                    'db_copy',
                    isset($_POST['drop_if_exists']) && $_POST['drop_if_exists'] === 'true'
                )
            ) {
                $GLOBALS['_error'] = true;
                break;
            }

            // apply the triggers to the destination db+table
            if ($triggers) {
                $this->dbi->selectDb($_POST['newname']);
                foreach ($triggers as $trigger) {
                    $this->dbi->query($trigger['create']);
                    $GLOBALS['sql_query'] .= "\n" . $trigger['create'] . ';';
                }
            }

            // this does not apply to a rename operation
            if (! isset($_POST['add_constraints']) || empty($GLOBALS['sql_constraints_query'])) {
                continue;
            }

            $sqlContraints[] = $GLOBALS['sql_constraints_query'];
            unset($GLOBALS['sql_constraints_query']);
        }

        return $sqlContraints;
    }

    /**
     * Run the EVENT definition for selected database
     *
     * to avoid selecting alternatively the current and new db
     * we would need to modify the CREATE definitions to qualify
     * the db name
     *
     * @param string $db database name
     */
    public function runEventDefinitionsForDb($db): void
    {
        $event_names = $this->dbi->fetchResult(
            'SELECT EVENT_NAME FROM information_schema.EVENTS WHERE EVENT_SCHEMA= \''
            . $this->dbi->escapeString($db) . '\';'
        );
        if (! $event_names) {
            return;
        }

        foreach ($event_names as $event_name) {
            $this->dbi->selectDb($db);
            $tmp_query = $this->dbi->getDefinition($db, 'EVENT', $event_name);
            // collect for later display
            $GLOBALS['sql_query'] .= "\n" . $tmp_query;
            $this->dbi->selectDb($_POST['newname']);
            $this->dbi->query($tmp_query);
        }
    }

    /**
     * Handle the views, return the boolean value whether table rename/copy or not
     *
     * @param array  $views views as an array
     * @param bool   $move  whether database name is empty or not
     * @param string $db    database name
     */
    public function handleTheViews(array $views, $move, $db): void
    {
        // Add DROP IF EXIST to CREATE VIEW query, to remove stand-in VIEW that was created earlier.
        foreach ($views as $view) {
            $copying_succeeded = Table::moveCopy(
                $db,
                $view,
                $_POST['newname'],
                $view,
                'structure',
                $move,
                'db_copy',
                true
            );
            if (! $copying_succeeded) {
                $GLOBALS['_error'] = true;
                break;
            }
        }
    }

    /**
     * Adjust the privileges after Renaming the db
     *
     * @param string $oldDb   Database name before renaming
     * @param string $newname New Database name requested
     */
    public function adjustPrivilegesMoveDb($oldDb, $newname): void
    {
        if (
            ! $GLOBALS['db_priv'] || ! $GLOBALS['table_priv']
            || ! $GLOBALS['col_priv'] || ! $GLOBALS['proc_priv']
            || ! $GLOBALS['is_reload_priv']
        ) {
            return;
        }

        $this->dbi->selectDb('mysql');
        $newname = str_replace('_', '\_', $newname);
        $oldDb = str_replace('_', '\_', $oldDb);

        // For Db specific privileges
        $query_db_specific = 'UPDATE ' . Util::backquote('db')
            . 'SET Db = \'' . $this->dbi->escapeString($newname)
            . '\' where Db = \'' . $this->dbi->escapeString($oldDb) . '\';';
        $this->dbi->query($query_db_specific);

        // For table specific privileges
        $query_table_specific = 'UPDATE ' . Util::backquote('tables_priv')
            . 'SET Db = \'' . $this->dbi->escapeString($newname)
            . '\' where Db = \'' . $this->dbi->escapeString($oldDb) . '\';';
        $this->dbi->query($query_table_specific);

        // For column specific privileges
        $query_col_specific = 'UPDATE ' . Util::backquote('columns_priv')
            . 'SET Db = \'' . $this->dbi->escapeString($newname)
            . '\' where Db = \'' . $this->dbi->escapeString($oldDb) . '\';';
        $this->dbi->query($query_col_specific);

        // For procedures specific privileges
        $query_proc_specific = 'UPDATE ' . Util::backquote('procs_priv')
            . 'SET Db = \'' . $this->dbi->escapeString($newname)
            . '\' where Db = \'' . $this->dbi->escapeString($oldDb) . '\';';
        $this->dbi->query($query_proc_specific);

        // Finally FLUSH the new privileges
        $this->dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    /**
     * Adjust the privileges after Copying the db
     *
     * @param string $oldDb   Database name before copying
     * @param string $newname New Database name requested
     */
    public function adjustPrivilegesCopyDb($oldDb, $newname): void
    {
        if (
            ! $GLOBALS['db_priv'] || ! $GLOBALS['table_priv']
            || ! $GLOBALS['col_priv'] || ! $GLOBALS['proc_priv']
            || ! $GLOBALS['is_reload_priv']
        ) {
            return;
        }

        $this->dbi->selectDb('mysql');
        $newname = str_replace('_', '\_', $newname);
        $oldDb = str_replace('_', '\_', $oldDb);

        $query_db_specific_old = 'SELECT * FROM '
            . Util::backquote('db') . ' WHERE '
            . 'Db = "' . $oldDb . '";';

        $old_privs_db = $this->dbi->fetchResult($query_db_specific_old, 0);

        foreach ($old_privs_db as $old_priv) {
            $newDb_db_privs_query = 'INSERT INTO ' . Util::backquote('db')
                . ' VALUES("' . $old_priv[0] . '", "' . $newname . '"';
            $privCount = count($old_priv);
            for ($i = 2; $i < $privCount; $i++) {
                $newDb_db_privs_query .= ', "' . $old_priv[$i] . '"';
            }

            $newDb_db_privs_query .= ')';

            $this->dbi->query($newDb_db_privs_query);
        }

        // For Table Specific privileges
        $query_table_specific_old = 'SELECT * FROM '
            . Util::backquote('tables_priv') . ' WHERE '
            . 'Db = "' . $oldDb . '";';

        $old_privs_table = $this->dbi->fetchResult($query_table_specific_old, 0);

        foreach ($old_privs_table as $old_priv) {
            $newDb_table_privs_query = 'INSERT INTO ' . Util::backquote(
                'tables_priv'
            ) . ' VALUES("' . $old_priv[0] . '", "' . $newname . '", "'
            . $old_priv[2] . '", "' . $old_priv[3] . '", "' . $old_priv[4]
            . '", "' . $old_priv[5] . '", "' . $old_priv[6] . '", "'
            . $old_priv[7] . '");';

            $this->dbi->query($newDb_table_privs_query);
        }

        // For Column Specific privileges
        $query_col_specific_old = 'SELECT * FROM '
            . Util::backquote('columns_priv') . ' WHERE '
            . 'Db = "' . $oldDb . '";';

        $old_privs_col = $this->dbi->fetchResult($query_col_specific_old, 0);

        foreach ($old_privs_col as $old_priv) {
            $newDb_col_privs_query = 'INSERT INTO ' . Util::backquote(
                'columns_priv'
            ) . ' VALUES("' . $old_priv[0] . '", "' . $newname . '", "'
            . $old_priv[2] . '", "' . $old_priv[3] . '", "' . $old_priv[4]
            . '", "' . $old_priv[5] . '", "' . $old_priv[6] . '");';

            $this->dbi->query($newDb_col_privs_query);
        }

        // For Procedure Specific privileges
        $query_proc_specific_old = 'SELECT * FROM '
            . Util::backquote('procs_priv') . ' WHERE '
            . 'Db = "' . $oldDb . '";';

        $old_privs_proc = $this->dbi->fetchResult($query_proc_specific_old, 0);

        foreach ($old_privs_proc as $old_priv) {
            $newDb_proc_privs_query = 'INSERT INTO ' . Util::backquote(
                'procs_priv'
            ) . ' VALUES("' . $old_priv[0] . '", "' . $newname . '", "'
            . $old_priv[2] . '", "' . $old_priv[3] . '", "' . $old_priv[4]
            . '", "' . $old_priv[5] . '", "' . $old_priv[6] . '", "'
            . $old_priv[7] . '");';

            $this->dbi->query($newDb_proc_privs_query);
        }

        // Finally FLUSH the new privileges
        $this->dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    /**
     * Create all accumulated constraints
     *
     * @param array $sqlConstratints array of sql constraints for the database
     */
    public function createAllAccumulatedConstraints(array $sqlConstratints): void
    {
        $this->dbi->selectDb($_POST['newname']);
        foreach ($sqlConstratints as $one_query) {
            $this->dbi->query($one_query);
            // and prepare to display them
            $GLOBALS['sql_query'] .= "\n" . $one_query;
        }
    }

    /**
     * Duplicate the bookmarks for the db (done once for each db)
     *
     * @param bool   $_error whether table rename/copy or not
     * @param string $db     database name
     */
    public function duplicateBookmarks($_error, $db): void
    {
        if ($_error || $db == $_POST['newname']) {
            return;
        }

        $get_fields = [
            'user',
            'label',
            'query',
        ];
        $where_fields = ['dbase' => $db];
        $new_fields = ['dbase' => $_POST['newname']];
        Table::duplicateInfo('bookmarkwork', 'bookmark', $get_fields, $where_fields, $new_fields);
    }

    /**
     * Get array of possible row formats
     *
     * @return array
     */
    public function getPossibleRowFormat()
    {
        // the outer array is for engines, the inner array contains the dropdown
        // option values as keys then the dropdown option labels

        $possible_row_formats = [
            'ARCHIVE' => ['COMPRESSED' => 'COMPRESSED'],
            'ARIA' => [
                'FIXED' => 'FIXED',
                'DYNAMIC' => 'DYNAMIC',
                'PAGE' => 'PAGE',
            ],
            'MARIA' => [
                'FIXED' => 'FIXED',
                'DYNAMIC' => 'DYNAMIC',
                'PAGE' => 'PAGE',
            ],
            'MYISAM' => [
                'FIXED' => 'FIXED',
                'DYNAMIC' => 'DYNAMIC',
            ],
            'PBXT' => [
                'FIXED' => 'FIXED',
                'DYNAMIC' => 'DYNAMIC',
            ],
            'INNODB' => [
                'COMPACT' => 'COMPACT',
                'REDUNDANT' => 'REDUNDANT',
            ],
        ];

        /** @var Innodb $innodbEnginePlugin */
        $innodbEnginePlugin = StorageEngine::getEngine('Innodb');
        $innodbPluginVersion = $innodbEnginePlugin->getInnodbPluginVersion();
        $innodb_file_format = '';
        if (! empty($innodbPluginVersion)) {
            $innodb_file_format = $innodbEnginePlugin->getInnodbFileFormat() ?? '';
        }

        /**
         * Newer MySQL/MariaDB always return empty a.k.a '' on $innodb_file_format otherwise
         * old versions of MySQL/MariaDB must be returning something or not empty.
         * This patch is to support newer MySQL/MariaDB while also for backward compatibilities.
         */
        if (
            (strtolower($innodb_file_format) === 'barracuda') || ($innodb_file_format == '')
            && $innodbEnginePlugin->supportsFilePerTable()
        ) {
            $possible_row_formats['INNODB']['DYNAMIC'] = 'DYNAMIC';
            $possible_row_formats['INNODB']['COMPRESSED'] = 'COMPRESSED';
        }

        return $possible_row_formats;
    }

    /**
     * @return array<string, string>
     */
    public function getPartitionMaintenanceChoices(): array
    {
        global $db, $table;

        $choices = [
            'ANALYZE' => __('Analyze'),
            'CHECK' => __('Check'),
            'OPTIMIZE' => __('Optimize'),
            'REBUILD' => __('Rebuild'),
            'REPAIR' => __('Repair'),
            'TRUNCATE' => __('Truncate'),
        ];

        $partitionMethod = Partition::getPartitionMethod($db, $table);

        // add COALESCE or DROP option to choices array depending on Partition method
        if (
            $partitionMethod === 'RANGE'
            || $partitionMethod === 'RANGE COLUMNS'
            || $partitionMethod === 'LIST'
            || $partitionMethod === 'LIST COLUMNS'
        ) {
            $choices['DROP'] = __('Drop');
        } else {
            $choices['COALESCE'] = __('Coalesce');
        }

        return $choices;
    }

    /**
     * @param array $urlParams          Array of url parameters.
     * @param bool  $hasRelationFeature If relation feature is enabled.
     *
     * @return array
     */
    public function getForeignersForReferentialIntegrityCheck(
        array $urlParams,
        $hasRelationFeature
    ): array {
        global $db, $table;

        if (! $hasRelationFeature) {
            return [];
        }

        $foreigners = [];
        $this->dbi->selectDb($db);
        $foreign = $this->relation->getForeigners($db, $table, '', 'internal');

        foreach ($foreign as $master => $arr) {
            $joinQuery = 'SELECT '
                . Util::backquote($table) . '.*'
                . ' FROM ' . Util::backquote($table)
                . ' LEFT JOIN '
                . Util::backquote($arr['foreign_db'])
                . '.'
                . Util::backquote($arr['foreign_table']);

            if ($arr['foreign_table'] == $table) {
                $foreignTable = $table . '1';
                $joinQuery .= ' AS ' . Util::backquote($foreignTable);
            } else {
                $foreignTable = $arr['foreign_table'];
            }

            $joinQuery .= ' ON '
                . Util::backquote($table) . '.'
                . Util::backquote($master)
                . ' = '
                . Util::backquote($arr['foreign_db'])
                . '.'
                . Util::backquote($foreignTable) . '.'
                . Util::backquote($arr['foreign_field'])
                . ' WHERE '
                . Util::backquote($arr['foreign_db'])
                . '.'
                . Util::backquote($foreignTable) . '.'
                . Util::backquote($arr['foreign_field'])
                . ' IS NULL AND '
                . Util::backquote($table) . '.'
                . Util::backquote($master)
                . ' IS NOT NULL';
            $thisUrlParams = array_merge(
                $urlParams,
                [
                    'sql_query' => $joinQuery,
                    'sql_signature' => Core::signSqlQuery($joinQuery),
                ]
            );

            $foreigners[] = [
                'params' => $thisUrlParams,
                'master' => $master,
                'db' => $arr['foreign_db'],
                'table' => $arr['foreign_table'],
                'field' => $arr['foreign_field'],
            ];
        }

        return $foreigners;
    }

    /**
     * Get table alters array
     *
     * @param Table  $pma_table           The Table object
     * @param string $pack_keys           pack keys
     * @param string $checksum            value of checksum
     * @param string $page_checksum       value of page checksum
     * @param string $delay_key_write     delay key write
     * @param string $row_format          row format
     * @param string $newTblStorageEngine table storage engine
     * @param string $transactional       value of transactional
     * @param string $tbl_collation       collation of the table
     *
     * @return array
     */
    public function getTableAltersArray(
        $pma_table,
        $pack_keys,
        $checksum,
        $page_checksum,
        $delay_key_write,
        $row_format,
        $newTblStorageEngine,
        $transactional,
        $tbl_collation
    ) {
        global $auto_increment;

        $table_alters = [];

        if (isset($_POST['comment']) && urldecode($_POST['prev_comment']) !== $_POST['comment']) {
            $table_alters[] = 'COMMENT = \''
                . $this->dbi->escapeString($_POST['comment']) . '\'';
        }

        if (
            ! empty($newTblStorageEngine)
            && mb_strtolower($newTblStorageEngine) !== mb_strtolower($GLOBALS['tbl_storage_engine'])
        ) {
            $table_alters[] = 'ENGINE = ' . $newTblStorageEngine;
        }

        if (! empty($_POST['tbl_collation']) && $_POST['tbl_collation'] !== $tbl_collation) {
            $table_alters[] = 'DEFAULT '
                . Util::getCharsetQueryPart($_POST['tbl_collation'] ?? '');
        }

        if (
            $pma_table->isEngine(['MYISAM', 'ARIA', 'ISAM'])
            && isset($_POST['new_pack_keys'])
            && $_POST['new_pack_keys'] != (string) $pack_keys
        ) {
            $table_alters[] = 'pack_keys = ' . $_POST['new_pack_keys'];
        }

        $newChecksum = empty($_POST['new_checksum']) ? '0' : '1';
        if ($pma_table->isEngine(['MYISAM', 'ARIA']) && $newChecksum !== $checksum) {
            $table_alters[] = 'checksum = ' . $newChecksum;
        }

        $newTransactional = empty($_POST['new_transactional']) ? '0' : '1';
        if ($pma_table->isEngine('ARIA') && $newTransactional !== $transactional) {
            $table_alters[] = 'TRANSACTIONAL = ' . $newTransactional;
        }

        $newPageChecksum = empty($_POST['new_page_checksum']) ? '0' : '1';
        if ($pma_table->isEngine('ARIA') && $newPageChecksum !== $page_checksum) {
            $table_alters[] = 'PAGE_CHECKSUM = ' . $newPageChecksum;
        }

        $newDelayKeyWrite = empty($_POST['new_delay_key_write']) ? '0' : '1';
        if ($pma_table->isEngine(['MYISAM', 'ARIA']) && $newDelayKeyWrite !== $delay_key_write) {
            $table_alters[] = 'delay_key_write = ' . $newDelayKeyWrite;
        }

        if (
            $pma_table->isEngine(['MYISAM', 'ARIA', 'INNODB', 'PBXT', 'ROCKSDB'])
            && ! empty($_POST['new_auto_increment'])
            && (! isset($auto_increment)
            || $_POST['new_auto_increment'] !== $auto_increment)
            && $_POST['new_auto_increment'] !== $_POST['hidden_auto_increment']
        ) {
            $table_alters[] = 'auto_increment = '
                . $this->dbi->escapeString($_POST['new_auto_increment']);
        }

        if (! empty($_POST['new_row_format'])) {
            $newRowFormat = $_POST['new_row_format'];
            $newRowFormatLower = mb_strtolower($newRowFormat);
            if (
                $pma_table->isEngine(['MYISAM', 'ARIA', 'INNODB', 'PBXT'])
                && (strlen($row_format) === 0
                || $newRowFormatLower !== mb_strtolower($row_format))
            ) {
                $table_alters[] = 'ROW_FORMAT = '
                    . $this->dbi->escapeString($newRowFormat);
            }
        }

        return $table_alters;
    }

    /**
     * Get warning messages array
     *
     * @return string[]
     */
    public function getWarningMessagesArray(): array
    {
        $warning_messages = [];
        foreach ($this->dbi->getWarnings() as $warning) {
            // In MariaDB 5.1.44, when altering a table from Maria to MyISAM
            // and if TRANSACTIONAL was set, the system reports an error;
            // I discussed with a Maria developer and he agrees that this
            // should not be reported with a Level of Error, so here
            // I just ignore it. But there are other 1478 messages
            // that it's better to show.
            if (
                isset($_POST['new_tbl_storage_engine'])
                && $_POST['new_tbl_storage_engine'] === 'MyISAM'
                && $warning->code === 1478
                && $warning->level === 'Error'
            ) {
                continue;
            }

            $warning_messages[] = (string) $warning;
        }

        return $warning_messages;
    }

    /**
     * Adjust the privileges after renaming/moving a table
     *
     * @param string $oldDb    Database name before table renaming/moving table
     * @param string $oldTable Table name before table renaming/moving table
     * @param string $newDb    Database name after table renaming/ moving table
     * @param string $newTable Table name after table renaming/moving table
     */
    public function adjustPrivilegesRenameOrMoveTable($oldDb, $oldTable, $newDb, $newTable): void
    {
        if (! $GLOBALS['table_priv'] || ! $GLOBALS['col_priv'] || ! $GLOBALS['is_reload_priv']) {
            return;
        }

        $this->dbi->selectDb('mysql');

        // For table specific privileges
        $query_table_specific = 'UPDATE ' . Util::backquote('tables_priv')
            . 'SET Db = \'' . $this->dbi->escapeString($newDb)
            . '\', Table_name = \'' . $this->dbi->escapeString($newTable)
            . '\' where Db = \'' . $this->dbi->escapeString($oldDb)
            . '\' AND Table_name = \'' . $this->dbi->escapeString($oldTable)
            . '\';';
        $this->dbi->query($query_table_specific);

        // For column specific privileges
        $query_col_specific = 'UPDATE ' . Util::backquote('columns_priv')
            . 'SET Db = \'' . $this->dbi->escapeString($newDb)
            . '\', Table_name = \'' . $this->dbi->escapeString($newTable)
            . '\' where Db = \'' . $this->dbi->escapeString($oldDb)
            . '\' AND Table_name = \'' . $this->dbi->escapeString($oldTable)
            . '\';';
        $this->dbi->query($query_col_specific);

        // Finally FLUSH the new privileges
        $this->dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    /**
     * Adjust the privileges after copying a table
     *
     * @param string $oldDb    Database name before table copying
     * @param string $oldTable Table name before table copying
     * @param string $newDb    Database name after table copying
     * @param string $newTable Table name after table copying
     */
    public function adjustPrivilegesCopyTable($oldDb, $oldTable, $newDb, $newTable): void
    {
        if (! $GLOBALS['table_priv'] || ! $GLOBALS['col_priv'] || ! $GLOBALS['is_reload_priv']) {
            return;
        }

        $this->dbi->selectDb('mysql');

        // For Table Specific privileges
        $query_table_specific_old = 'SELECT * FROM '
            . Util::backquote('tables_priv') . ' where '
            . 'Db = "' . $oldDb . '" AND Table_name = "' . $oldTable . '";';

        $old_privs_table = $this->dbi->fetchResult($query_table_specific_old, 0);

        foreach ($old_privs_table as $old_priv) {
            $newDb_table_privs_query = 'INSERT INTO '
                . Util::backquote('tables_priv') . ' VALUES("'
                . $old_priv[0] . '", "' . $newDb . '", "' . $old_priv[2] . '", "'
                . $newTable . '", "' . $old_priv[4] . '", "' . $old_priv[5]
                . '", "' . $old_priv[6] . '", "' . $old_priv[7] . '");';

            $this->dbi->query($newDb_table_privs_query);
        }

        // For Column Specific privileges
        $query_col_specific_old = 'SELECT * FROM '
            . Util::backquote('columns_priv') . ' WHERE '
            . 'Db = "' . $oldDb . '" AND Table_name = "' . $oldTable . '";';

        $old_privs_col = $this->dbi->fetchResult($query_col_specific_old, 0);

        foreach ($old_privs_col as $old_priv) {
            $newDb_col_privs_query = 'INSERT INTO '
                . Util::backquote('columns_priv') . ' VALUES("'
                . $old_priv[0] . '", "' . $newDb . '", "' . $old_priv[2] . '", "'
                . $newTable . '", "' . $old_priv[4] . '", "' . $old_priv[5]
                . '", "' . $old_priv[6] . '");';

            $this->dbi->query($newDb_col_privs_query);
        }

        // Finally FLUSH the new privileges
        $this->dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    /**
     * Change all collations and character sets of all columns in table
     *
     * @param string $db            Database name
     * @param string $table         Table name
     * @param string $tbl_collation Collation Name
     */
    public function changeAllColumnsCollation($db, $table, $tbl_collation): void
    {
        $this->dbi->selectDb($db);

        $change_all_collations_query = 'ALTER TABLE '
            . Util::backquote($table)
            . ' CONVERT TO';

        [$charset] = explode('_', $tbl_collation);

        $change_all_collations_query .= ' CHARACTER SET ' . $charset
            . ($charset == $tbl_collation ? '' : ' COLLATE ' . $tbl_collation);

        $this->dbi->query($change_all_collations_query);
    }

    /**
     * Move or copy a table
     *
     * @param string $db    current database name
     * @param string $table current table name
     */
    public function moveOrCopyTable($db, $table): Message
    {
        /**
         * Selects the database to work with
         */
        $this->dbi->selectDb($db);

        /**
         * $_POST['target_db'] could be empty in case we came from an input field
         * (when there are many databases, no drop-down)
         */
        $targetDb = $db;
        if (isset($_POST['target_db']) && is_string($_POST['target_db']) && strlen($_POST['target_db']) > 0) {
            $targetDb = $_POST['target_db'];
        }

        /**
         * A target table name has been sent to this script -> do the work
         */
        if (isset($_POST['new_name']) && is_scalar($_POST['new_name']) && strlen((string) $_POST['new_name']) > 0) {
            if ($db == $targetDb && $table == $_POST['new_name']) {
                if (isset($_POST['submit_move'])) {
                    $message = Message::error(__('Can\'t move table to same one!'));
                } else {
                    $message = Message::error(__('Can\'t copy table to same one!'));
                }
            } else {
                Table::moveCopy(
                    $db,
                    $table,
                    $targetDb,
                    (string) $_POST['new_name'],
                    $_POST['what'],
                    isset($_POST['submit_move']),
                    'one_table',
                    isset($_POST['drop_if_exists']) && $_POST['drop_if_exists'] === 'true'
                );

                if (isset($_POST['adjust_privileges']) && ! empty($_POST['adjust_privileges'])) {
                    if (isset($_POST['submit_move'])) {
                        $this->adjustPrivilegesRenameOrMoveTable($db, $table, $targetDb, (string) $_POST['new_name']);
                    } else {
                        $this->adjustPrivilegesCopyTable($db, $table, $targetDb, (string) $_POST['new_name']);
                    }

                    if (isset($_POST['submit_move'])) {
                        $message = Message::success(
                            __(
                                'Table %s has been moved to %s. Privileges have been adjusted.'
                            )
                        );
                    } else {
                        $message = Message::success(
                            __(
                                'Table %s has been copied to %s. Privileges have been adjusted.'
                            )
                        );
                    }
                } else {
                    if (isset($_POST['submit_move'])) {
                        $message = Message::success(
                            __('Table %s has been moved to %s.')
                        );
                    } else {
                        $message = Message::success(
                            __('Table %s has been copied to %s.')
                        );
                    }
                }

                $old = Util::backquote($db) . '.'
                    . Util::backquote($table);
                $message->addParam($old);

                $new_name = (string) $_POST['new_name'];
                if ($this->dbi->getLowerCaseNames() === '1') {
                    $new_name = strtolower($new_name);
                }

                $GLOBALS['table'] = $new_name;

                $new = Util::backquote($targetDb) . '.'
                    . Util::backquote($new_name);
                $message->addParam($new);
            }
        } else {
            /**
             * No new name for the table!
             */
            $message = Message::error(__('The table name is empty!'));
        }

        return $message;
    }
}
