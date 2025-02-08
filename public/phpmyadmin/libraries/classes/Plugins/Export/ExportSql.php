<?php
/**
 * Set of functions used to build SQL dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\MessageOnlyPropertyItem;
use PhpMyAdmin\Properties\Options\Items\NumberPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\Util;
use PhpMyAdmin\Version;

use function __;
use function array_keys;
use function bin2hex;
use function count;
use function defined;
use function explode;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strtotime;
use function strtoupper;
use function trigger_error;

use const E_USER_ERROR;
use const PHP_VERSION;

/**
 * Handles the export for the SQL class
 */
class ExportSql extends ExportPlugin
{
    /**
     * Whether charset header was sent.
     *
     * @var bool
     */
    private $sentCharset = false;

    /** @var string */
    private $sqlViews = '';

    protected function init(): void
    {
        // Avoids undefined variables, use NULL so isset() returns false
        if (isset($GLOBALS['sql_backquotes'])) {
            return;
        }

        $GLOBALS['sql_backquotes'] = null;
    }

    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'sql';
    }

    protected function setProperties(): ExportPluginProperties
    {
        global $plugin_param, $dbi;

        $hideSql = false;
        $hideStructure = false;
        if ($plugin_param['export_type'] === 'table' && ! $plugin_param['single_table']) {
            $hideStructure = true;
            $hideSql = true;
        }

        // In case we have `raw_query` parameter set,
        // we initialize SQL option
        if (isset($_REQUEST['raw_query'])) {
            $hideStructure = false;
            $hideSql = false;
        }

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('SQL');
        $exportPluginProperties->setExtension('sql');
        $exportPluginProperties->setMimeType('text/x-sql');

        if ($hideSql) {
            return $exportPluginProperties;
        }

        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');

        // comments
        $subgroup = new OptionsPropertySubgroup('include_comments');
        $leaf = new BoolPropertyItem(
            'include_comments',
            __(
                'Display comments <i>(includes info such as export timestamp, PHP version, and server version)</i>'
            )
        );
        $subgroup->setSubgroupHeader($leaf);

        $leaf = new TextPropertyItem(
            'header_comment',
            __('Additional custom header comment (\n splits lines):')
        );
        $subgroup->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'dates',
            __(
                'Include a timestamp of when databases were created, last updated, and last checked'
            )
        );
        $subgroup->addProperty($leaf);
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->relationFeature !== null) {
            $leaf = new BoolPropertyItem(
                'relation',
                __('Display foreign key relationships')
            );
            $subgroup->addProperty($leaf);
        }

        if ($relationParameters->browserTransformationFeature !== null) {
            $leaf = new BoolPropertyItem(
                'mime',
                __('Display media types')
            );
            $subgroup->addProperty($leaf);
        }

        $generalOptions->addProperty($subgroup);

        // enclose in a transaction
        $leaf = new BoolPropertyItem(
            'use_transaction',
            __('Enclose export in a transaction')
        );
        $leaf->setDoc(
            [
                'programs',
                'mysqldump',
                'option_mysqldump_single-transaction',
            ]
        );
        $generalOptions->addProperty($leaf);

        // disable foreign key checks
        $leaf = new BoolPropertyItem(
            'disable_fk',
            __('Disable foreign key checks')
        );
        $leaf->setDoc(
            [
                'manual_MySQL_Database_Administration',
                'server-system-variables',
                'sysvar_foreign_key_checks',
            ]
        );
        $generalOptions->addProperty($leaf);

        // export views as tables
        $leaf = new BoolPropertyItem(
            'views_as_tables',
            __('Export views as tables')
        );
        $generalOptions->addProperty($leaf);

        // export metadata
        $leaf = new BoolPropertyItem(
            'metadata',
            __('Export metadata')
        );
        $generalOptions->addProperty($leaf);

        // compatibility maximization
        $compats = $dbi->getCompatibilities();
        if (count($compats) > 0) {
            $values = [];
            foreach ($compats as $val) {
                $values[$val] = $val;
            }

            $leaf = new SelectPropertyItem(
                'compatibility',
                __(
                    'Database system or older MySQL server to maximize output compatibility with:'
                )
            );
            $leaf->setValues($values);
            $leaf->setDoc(
                [
                    'manual_MySQL_Database_Administration',
                    'Server_SQL_mode',
                ]
            );
            $generalOptions->addProperty($leaf);

            unset($values);
        }

        // what to dump (structure/data/both)
        $subgroup = new OptionsPropertySubgroup(
            'dump_table',
            __('Dump table')
        );
        $leaf = new RadioPropertyItem('structure_or_data');
        $leaf->setValues(
            [
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data'),
            ]
        );
        $subgroup->setSubgroupHeader($leaf);
        $generalOptions->addProperty($subgroup);

        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // structure options main group
        if (! $hideStructure) {
            $structureOptions = new OptionsPropertyMainGroup(
                'structure',
                __('Object creation options')
            );
            $structureOptions->setForce('data');

            // begin SQL Statements
            $subgroup = new OptionsPropertySubgroup();
            $leaf = new MessageOnlyPropertyItem(
                'add_statements',
                __('Add statements:')
            );
            $subgroup->setSubgroupHeader($leaf);

            // server export options
            if ($plugin_param['export_type'] === 'server') {
                $leaf = new BoolPropertyItem(
                    'drop_database',
                    sprintf(__('Add %s statement'), '<code>DROP DATABASE IF EXISTS</code>')
                );
                $subgroup->addProperty($leaf);
            }

            if ($plugin_param['export_type'] === 'database') {
                $createClause = '<code>CREATE DATABASE / USE</code>';
                $leaf = new BoolPropertyItem(
                    'create_database',
                    sprintf(__('Add %s statement'), $createClause)
                );
                $subgroup->addProperty($leaf);
            }

            if ($plugin_param['export_type'] === 'table') {
                $dropClause = $dbi->getTable($GLOBALS['db'], $GLOBALS['table'])->isView()
                    ? '<code>DROP VIEW</code>'
                    : '<code>DROP TABLE</code>';
            } else {
                $dropClause = '<code>DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT</code>';
            }

            $dropClause .= '<code> / TRIGGER</code>';

            $leaf = new BoolPropertyItem(
                'drop_table',
                sprintf(__('Add %s statement'), $dropClause)
            );
            $subgroup->addProperty($leaf);

            $subgroupCreateTable = new OptionsPropertySubgroup();

            // Add table structure option
            $leaf = new BoolPropertyItem(
                'create_table',
                sprintf(__('Add %s statement'), '<code>CREATE TABLE</code>')
            );
            $subgroupCreateTable->setSubgroupHeader($leaf);

            $leaf = new BoolPropertyItem(
                'if_not_exists',
                '<code>IF NOT EXISTS</code> ' . __(
                    '(less efficient as indexes will be generated during table creation)'
                )
            );
            $subgroupCreateTable->addProperty($leaf);

            $leaf = new BoolPropertyItem(
                'auto_increment',
                sprintf(__('%s value'), '<code>AUTO_INCREMENT</code>')
            );
            $subgroupCreateTable->addProperty($leaf);

            $subgroup->addProperty($subgroupCreateTable);

            // Add view option
            $subgroupCreateView = new OptionsPropertySubgroup();
            $leaf = new BoolPropertyItem(
                'create_view',
                sprintf(__('Add %s statement'), '<code>CREATE VIEW</code>')
            );
            $subgroupCreateView->setSubgroupHeader($leaf);

            $leaf = new BoolPropertyItem(
                'simple_view_export',
                /* l10n: Allow simplifying exported view syntax to only "CREATE VIEW" */
                __('Use simple view export')
            );
            $subgroupCreateView->addProperty($leaf);

            $leaf = new BoolPropertyItem(
                'view_current_user',
                __('Exclude definition of current user')
            );
            $subgroupCreateView->addProperty($leaf);

            $leaf = new BoolPropertyItem(
                'or_replace_view',
                sprintf(__('%s view'), '<code>OR REPLACE</code>')
            );
            $subgroupCreateView->addProperty($leaf);

            $subgroup->addProperty($subgroupCreateView);

            $leaf = new BoolPropertyItem(
                'procedure_function',
                sprintf(
                    __('Add %s statement'),
                    '<code>CREATE PROCEDURE / FUNCTION / EVENT</code>'
                )
            );
            $subgroup->addProperty($leaf);

            // Add triggers option
            $leaf = new BoolPropertyItem(
                'create_trigger',
                sprintf(__('Add %s statement'), '<code>CREATE TRIGGER</code>')
            );
            $subgroup->addProperty($leaf);

            $structureOptions->addProperty($subgroup);

            $leaf = new BoolPropertyItem(
                'backquotes',
                __(
                    'Enclose table and column names with backquotes '
                    . '<i>(Protects column and table names formed with'
                    . ' special characters or keywords)</i>'
                )
            );

            $structureOptions->addProperty($leaf);

            // add the main group to the root group
            $exportSpecificOptions->addProperty($structureOptions);
        }

        // begin Data options
        $dataOptions = new OptionsPropertyMainGroup(
            'data',
            __('Data creation options')
        );
        $dataOptions->setForce('structure');
        $leaf = new BoolPropertyItem(
            'truncate',
            __('Truncate table before insert')
        );
        $dataOptions->addProperty($leaf);

        // begin SQL Statements
        $subgroup = new OptionsPropertySubgroup();
        $leaf = new MessageOnlyPropertyItem(
            __('Instead of <code>INSERT</code> statements, use:')
        );
        $subgroup->setSubgroupHeader($leaf);

        $leaf = new BoolPropertyItem(
            'delayed',
            __('<code>INSERT DELAYED</code> statements')
        );
        $leaf->setDoc(
            [
                'manual_MySQL_Database_Administration',
                'insert_delayed',
            ]
        );
        $subgroup->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'ignore',
            __('<code>INSERT IGNORE</code> statements')
        );
        $leaf->setDoc(
            [
                'manual_MySQL_Database_Administration',
                'insert',
            ]
        );
        $subgroup->addProperty($leaf);
        $dataOptions->addProperty($subgroup);

        // Function to use when dumping dat
        $leaf = new SelectPropertyItem(
            'type',
            __('Function to use when dumping data:')
        );
        $leaf->setValues(
            [
                'INSERT' => 'INSERT',
                'UPDATE' => 'UPDATE',
                'REPLACE' => 'REPLACE',
            ]
        );
        $dataOptions->addProperty($leaf);

        /* Syntax to use when inserting data */
        $subgroup = new OptionsPropertySubgroup();
        $leaf = new MessageOnlyPropertyItem(
            null,
            __('Syntax to use when inserting data:')
        );
        $subgroup->setSubgroupHeader($leaf);
        $leaf = new RadioPropertyItem(
            'insert_syntax',
            __('<code>INSERT IGNORE</code> statements')
        );
        $leaf->setValues(
            [
                'complete' => __(
                    'include column names in every <code>INSERT</code> statement'
                    . ' <br> &nbsp; &nbsp; &nbsp; Example: <code>INSERT INTO'
                    . ' tbl_name (col_A,col_B,col_C) VALUES (1,2,3)</code>'
                ),
                'extended' => __(
                    'insert multiple rows in every <code>INSERT</code> statement'
                    . '<br> &nbsp; &nbsp; &nbsp; Example: <code>INSERT INTO'
                    . ' tbl_name VALUES (1,2,3), (4,5,6), (7,8,9)</code>'
                ),
                'both' => __(
                    'both of the above<br> &nbsp; &nbsp; &nbsp; Example:'
                    . ' <code>INSERT INTO tbl_name (col_A,col_B,col_C) VALUES'
                    . ' (1,2,3), (4,5,6), (7,8,9)</code>'
                ),
                'none' => __(
                    'neither of the above<br> &nbsp; &nbsp; &nbsp; Example:'
                    . ' <code>INSERT INTO tbl_name VALUES (1,2,3)</code>'
                ),
            ]
        );
        $subgroup->addProperty($leaf);
        $dataOptions->addProperty($subgroup);

        // Max length of query
        $leaf = new NumberPropertyItem(
            'max_query_size',
            __('Maximal length of created query')
        );
        $dataOptions->addProperty($leaf);

        // Dump binary columns in hexadecimal
        $leaf = new BoolPropertyItem(
            'hex_for_binary',
            __(
                'Dump binary columns in hexadecimal notation <i>(for example, "abc" becomes 0x616263)</i>'
            )
        );
        $dataOptions->addProperty($leaf);

        // Dump time in UTC
        $leaf = new BoolPropertyItem(
            'utc_time',
            __(
                'Dump TIMESTAMP columns in UTC <i>(enables TIMESTAMP columns'
                . ' to be dumped and reloaded between servers in different'
                . ' time zones)</i>'
            )
        );
        $dataOptions->addProperty($leaf);

        // add the main group to the root group
        $exportSpecificOptions->addProperty($dataOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Generates SQL for routines export
     *
     * @param string $db        Database
     * @param array  $aliases   Aliases of db/table/columns
     * @param string $type      Type of exported routine
     * @param string $name      Verbose name of exported routine
     * @param array  $routines  List of routines to export
     * @param string $delimiter Delimiter to use in SQL
     *
     * @return string SQL query
     */
    protected function exportRoutineSQL(
        $db,
        array $aliases,
        $type,
        $name,
        array $routines,
        $delimiter
    ) {
        global $crlf, $cfg, $dbi;

        $text = $this->exportComment()
            . $this->exportComment($name)
            . $this->exportComment();

        $usedAlias = false;
        $procQuery = '';

        foreach ($routines as $routine) {
            if (! empty($GLOBALS['sql_drop_table'])) {
                $procQuery .= 'DROP ' . $type . ' IF EXISTS '
                    . Util::backquote($routine)
                    . $delimiter . $crlf;
            }

            $createQuery = $this->replaceWithAliases(
                $delimiter,
                $dbi->getDefinition($db, $type, $routine),
                $aliases,
                $db,
                '',
                $flag
            );
            if (! empty($createQuery) && $cfg['Export']['remove_definer_from_definitions']) {
                // Remove definer clause from routine definitions
                $parser = new Parser('DELIMITER ' . $delimiter . $crlf . $createQuery);
                $statement = $parser->statements[0];
                $statement->options->remove('DEFINER');
                $createQuery = $statement->build();
            }

            // One warning per database
            if ($flag) {
                $usedAlias = true;
            }

            $procQuery .= $createQuery . $delimiter . $crlf . $crlf;
        }

        if ($usedAlias) {
            $text .= $this->exportComment(
                __('It appears your database uses routines;')
            )
            . $this->exportComment(
                __('alias export may not work reliably in all cases.')
            )
            . $this->exportComment();
        }

        $text .= $procQuery;

        return $text;
    }

    /**
     * Exports routines (procedures and functions)
     *
     * @param string $db      Database
     * @param array  $aliases Aliases of db/table/columns
     */
    public function exportRoutines($db, array $aliases = []): bool
    {
        global $crlf, $dbi;

        $dbAlias = $db;
        $this->initAlias($aliases, $dbAlias);

        $text = '';
        $delimiter = '$$';

        $procedureNames = $dbi->getProceduresOrFunctions($db, 'PROCEDURE');
        $functionNames = $dbi->getProceduresOrFunctions($db, 'FUNCTION');

        if ($procedureNames || $functionNames) {
            $text .= $crlf
                . 'DELIMITER ' . $delimiter . $crlf;

            if ($procedureNames) {
                $text .= $this->exportRoutineSQL(
                    $db,
                    $aliases,
                    'PROCEDURE',
                    __('Procedures'),
                    $procedureNames,
                    $delimiter
                );
            }

            if ($functionNames) {
                $text .= $this->exportRoutineSQL(
                    $db,
                    $aliases,
                    'FUNCTION',
                    __('Functions'),
                    $functionNames,
                    $delimiter
                );
            }

            $text .= 'DELIMITER ;' . $crlf;
        }

        if (! empty($text)) {
            return $this->export->outputHandler($text);
        }

        return false;
    }

    /**
     * Possibly outputs comment
     *
     * @param string $text Text of comment
     *
     * @return string The formatted comment
     */
    private function exportComment($text = '')
    {
        if (isset($GLOBALS['sql_include_comments']) && $GLOBALS['sql_include_comments']) {
            // see https://dev.mysql.com/doc/refman/5.0/en/ansi-diff-comments.html
            if (empty($text)) {
                return '--' . $GLOBALS['crlf'];
            }

            $lines = preg_split("/\\r\\n|\\r|\\n/", $text);
            if ($lines === false) {
                return '--' . $GLOBALS['crlf'];
            }

            $result = [];
            foreach ($lines as $line) {
                $result[] = '-- ' . $line . $GLOBALS['crlf'];
            }

            return implode('', $result);
        }

        return '';
    }

    /**
     * Possibly outputs CRLF
     *
     * @return string crlf or nothing
     */
    private function possibleCRLF()
    {
        if (isset($GLOBALS['sql_include_comments']) && $GLOBALS['sql_include_comments']) {
            return $GLOBALS['crlf'];
        }

        return '';
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        global $crlf, $dbi;

        $foot = '';

        if (isset($GLOBALS['sql_disable_fk'])) {
            $foot .= 'SET FOREIGN_KEY_CHECKS=1;' . $crlf;
        }

        if (isset($GLOBALS['sql_use_transaction'])) {
            $foot .= 'COMMIT;' . $crlf;
        }

        // restore connection settings
        if ($this->sentCharset) {
            $foot .= $crlf
                . '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;'
                . $crlf
                . '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;'
                . $crlf
                . '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;'
                . $crlf;
            $this->sentCharset = false;
        }

        /* Restore timezone */
        if (isset($GLOBALS['sql_utc_time']) && $GLOBALS['sql_utc_time']) {
            $dbi->query('SET time_zone = "' . $GLOBALS['old_tz'] . '"');
        }

        return $this->export->outputHandler($foot);
    }

    /**
     * Outputs export header. It is the first method to be called, so all
     * the required variables are initialized here.
     */
    public function exportHeader(): bool
    {
        global $crlf, $cfg, $dbi;

        if (isset($GLOBALS['sql_compatibility'])) {
            $tmpCompat = $GLOBALS['sql_compatibility'];
            if ($tmpCompat === 'NONE') {
                $tmpCompat = '';
            }

            $dbi->tryQuery('SET SQL_MODE="' . $tmpCompat . '"');
            unset($tmpCompat);
        }

        $head = $this->exportComment('phpMyAdmin SQL Dump')
            . $this->exportComment('version ' . Version::VERSION)
            . $this->exportComment('https://www.phpmyadmin.net/')
            . $this->exportComment();
        $hostString = __('Host:') . ' ' . $cfg['Server']['host'];
        if (! empty($cfg['Server']['port'])) {
            $hostString .= ':' . $cfg['Server']['port'];
        }

        $head .= $this->exportComment($hostString);
        $head .= $this->exportComment(
            __('Generation Time:') . ' '
            . Util::localisedDate()
        )
        . $this->exportComment(
            __('Server version:') . ' ' . $dbi->getVersionString()
        )
        . $this->exportComment(__('PHP Version:') . ' ' . PHP_VERSION)
        . $this->possibleCRLF();

        if (isset($GLOBALS['sql_header_comment']) && ! empty($GLOBALS['sql_header_comment'])) {
            // '\n' is not a newline (like "\n" would be), it's the characters
            // backslash and n, as explained on the export interface
            $lines = explode('\n', $GLOBALS['sql_header_comment']);
            $head .= $this->exportComment();
            foreach ($lines as $oneLine) {
                $head .= $this->exportComment($oneLine);
            }

            $head .= $this->exportComment();
        }

        if (isset($GLOBALS['sql_disable_fk'])) {
            $head .= 'SET FOREIGN_KEY_CHECKS=0;' . $crlf;
        }

        // We want exported AUTO_INCREMENT columns to have still same value,
        // do this only for recent MySQL exports
        if (! isset($GLOBALS['sql_compatibility']) || $GLOBALS['sql_compatibility'] === 'NONE') {
            $head .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . $crlf;
        }

        if (isset($GLOBALS['sql_use_transaction'])) {
            $head .= 'START TRANSACTION;' . $crlf;
        }

        /* Change timezone if we should export timestamps in UTC */
        if (isset($GLOBALS['sql_utc_time']) && $GLOBALS['sql_utc_time']) {
            $head .= 'SET time_zone = "+00:00";' . $crlf;
            $GLOBALS['old_tz'] = $dbi
                ->fetchValue('SELECT @@session.time_zone');
            $dbi->query('SET time_zone = "+00:00"');
        }

        $head .= $this->possibleCRLF();

        if (! empty($GLOBALS['asfile'])) {
            // we are saving as file, therefore we provide charset information
            // so that a utility like the mysql client can interpret
            // the file correctly
            if (isset($GLOBALS['charset'], Charsets::$mysqlCharsetMap[$GLOBALS['charset']])) {
                // we got a charset from the export dialog
                $setNames = Charsets::$mysqlCharsetMap[$GLOBALS['charset']];
            } else {
                // by default we use the connection charset
                $setNames = Charsets::$mysqlCharsetMap['utf-8'];
            }

            if ($setNames === 'utf8' && $dbi->getVersion() > 50503) {
                $setNames = 'utf8mb4';
            }

            $head .= $crlf
                . '/*!40101 SET @OLD_CHARACTER_SET_CLIENT='
                . '@@CHARACTER_SET_CLIENT */;' . $crlf
                . '/*!40101 SET @OLD_CHARACTER_SET_RESULTS='
                . '@@CHARACTER_SET_RESULTS */;' . $crlf
                . '/*!40101 SET @OLD_COLLATION_CONNECTION='
                . '@@COLLATION_CONNECTION */;' . $crlf
                . '/*!40101 SET NAMES ' . $setNames . ' */;' . $crlf . $crlf;
            $this->sentCharset = true;
        }

        return $this->export->outputHandler($head);
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db         Database name
     * @param string $exportType 'server', 'database', 'table'
     * @param string $dbAlias    Aliases of db
     */
    public function exportDBCreate($db, $exportType, $dbAlias = ''): bool
    {
        global $crlf, $dbi;

        if (empty($dbAlias)) {
            $dbAlias = $db;
        }

        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }

        $exportStructure = ! isset($GLOBALS['sql_structure_or_data'])
            || in_array($GLOBALS['sql_structure_or_data'], ['structure', 'structure_and_data'], true);
        if ($exportStructure && isset($GLOBALS['sql_drop_database'])) {
            if (
                ! $this->export->outputHandler(
                    'DROP DATABASE IF EXISTS '
                    . Util::backquoteCompat(
                        $dbAlias,
                        $compat,
                        isset($GLOBALS['sql_backquotes'])
                    )
                    . ';' . $crlf
                )
            ) {
                return false;
            }
        }

        if ($exportType === 'database' && ! isset($GLOBALS['sql_create_database'])) {
            return true;
        }

        $createQuery = 'CREATE DATABASE IF NOT EXISTS '
            . Util::backquoteCompat($dbAlias, $compat, isset($GLOBALS['sql_backquotes']));
        $collation = $dbi->getDbCollation($db);
        if (mb_strpos($collation, '_')) {
            $createQuery .= ' DEFAULT CHARACTER SET '
                . mb_substr(
                    $collation,
                    0,
                    (int) mb_strpos($collation, '_')
                )
                . ' COLLATE ' . $collation;
        } else {
            $createQuery .= ' DEFAULT CHARACTER SET ' . $collation;
        }

        $createQuery .= ';' . $crlf;
        if (! $this->export->outputHandler($createQuery)) {
            return false;
        }

        return $this->exportUseStatement($dbAlias, $compat);
    }

    /**
     * Outputs USE statement
     *
     * @param string $db     db to use
     * @param string $compat sql compatibility
     */
    private function exportUseStatement($db, $compat): bool
    {
        global $crlf;

        if (isset($GLOBALS['sql_compatibility']) && $GLOBALS['sql_compatibility'] === 'NONE') {
            $result = $this->export->outputHandler(
                'USE '
                . Util::backquoteCompat(
                    $db,
                    $compat,
                    isset($GLOBALS['sql_backquotes'])
                )
                . ';' . $crlf
            );
        } else {
            $result = $this->export->outputHandler('USE ' . $db . ';' . $crlf);
        }

        return $result;
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Alias of db
     */
    public function exportDBHeader($db, $dbAlias = ''): bool
    {
        if (empty($dbAlias)) {
            $dbAlias = $db;
        }

        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }

        $head = $this->exportComment()
            . $this->exportComment(
                __('Database:') . ' '
                . Util::backquoteCompat(
                    $dbAlias,
                    $compat,
                    isset($GLOBALS['sql_backquotes'])
                )
            )
            . $this->exportComment();

        return $this->export->outputHandler($head);
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    public function exportDBFooter($db): bool
    {
        global $crlf;

        $result = true;

        //add indexes to the sql dump file
        if (isset($GLOBALS['sql_indexes'])) {
            $result = $this->export->outputHandler($GLOBALS['sql_indexes']);
            unset($GLOBALS['sql_indexes']);
        }

        //add auto increments to the sql dump file
        if (isset($GLOBALS['sql_auto_increments'])) {
            $result = $this->export->outputHandler($GLOBALS['sql_auto_increments']);
            unset($GLOBALS['sql_auto_increments']);
        }

        //add views to the sql dump file
        if ($this->sqlViews !== '') {
            $result = $this->export->outputHandler($this->sqlViews);
            $this->sqlViews = '';
        }

        //add constraints to the sql dump file
        if (isset($GLOBALS['sql_constraints'])) {
            $result = $this->export->outputHandler($GLOBALS['sql_constraints']);
            unset($GLOBALS['sql_constraints']);
        }

        return $result;
    }

    /**
     * Exports events
     *
     * @param string $db Database
     */
    public function exportEvents($db): bool
    {
        global $crlf, $cfg, $dbi;

        $text = '';
        $delimiter = '$$';

        $eventNames = $dbi->fetchResult(
            'SELECT EVENT_NAME FROM information_schema.EVENTS WHERE'
            . " EVENT_SCHEMA= '" . $dbi->escapeString($db)
            . "';"
        );

        if ($eventNames) {
            $text .= $crlf
                . 'DELIMITER ' . $delimiter . $crlf;

            $text .= $this->exportComment()
                . $this->exportComment(__('Events'))
                . $this->exportComment();

            foreach ($eventNames as $eventName) {
                if (! empty($GLOBALS['sql_drop_table'])) {
                    $text .= 'DROP EVENT IF EXISTS '
                        . Util::backquote($eventName)
                        . $delimiter . $crlf;
                }

                $eventDef = $dbi->getDefinition($db, 'EVENT', $eventName);
                if (! empty($eventDef) && $cfg['Export']['remove_definer_from_definitions']) {
                    // remove definer clause from the event definition
                    $parser = new Parser('DELIMITER ' . $delimiter . $crlf . $eventDef);
                    $statement = $parser->statements[0];
                    $statement->options->remove('DEFINER');
                    $eventDef = $statement->build();
                }

                $text .= $eventDef . $delimiter . $crlf . $crlf;
            }

            $text .= 'DELIMITER ;' . $crlf;
        }

        if (! empty($text)) {
            return $this->export->outputHandler($text);
        }

        return false;
    }

    /**
     * Exports metadata from Configuration Storage
     *
     * @param string       $db            database being exported
     * @param string|array $tables        table(s) being exported
     * @param array        $metadataTypes types of metadata to export
     */
    public function exportMetadata(
        $db,
        $tables,
        array $metadataTypes
    ): bool {
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->db === null) {
            return true;
        }

        $comment = $this->possibleCRLF()
            . $this->possibleCRLF()
            . $this->exportComment()
            . $this->exportComment(__('Metadata'))
            . $this->exportComment();
        if (! $this->export->outputHandler($comment)) {
            return false;
        }

        if (! $this->exportUseStatement((string) $relationParameters->db, $GLOBALS['sql_compatibility'])) {
            return false;
        }

        $r = 1;
        if (is_array($tables)) {
            // export metadata for each table
            foreach ($tables as $table) {
                $r &= (int) $this->exportConfigurationMetadata($db, $table, $metadataTypes);
            }

            // export metadata for the database
            $r &= (int) $this->exportConfigurationMetadata($db, null, $metadataTypes);
        } else {
            // export metadata for single table
            $r &= (int) $this->exportConfigurationMetadata($db, $tables, $metadataTypes);
        }

        return (bool) $r;
    }

    /**
     * Exports metadata from Configuration Storage
     *
     * @param string      $db            database being exported
     * @param string|null $table         table being exported
     * @param array       $metadataTypes types of metadata to export
     */
    private function exportConfigurationMetadata(
        $db,
        $table,
        array $metadataTypes
    ): bool {
        global $dbi;

        $relationParameters = $this->relation->getRelationParameters();
        $relationParams = $relationParameters->toArray();

        if (isset($table)) {
            $types = [
                'column_info' => 'db_name',
                'table_uiprefs' => 'db_name',
                'tracking' => 'db_name',
            ];
        } else {
            $types = [
                'bookmark' => 'dbase',
                'relation' => 'master_db',
                'pdf_pages' => 'db_name',
                'savedsearches' => 'db_name',
                'central_columns' => 'db_name',
            ];
        }

        $aliases = [];

        $comment = $this->possibleCRLF()
            . $this->exportComment();

        if (isset($table)) {
            $comment .= $this->exportComment(
                sprintf(
                    __('Metadata for table %s'),
                    $table
                )
            );
        } else {
            $comment .= $this->exportComment(
                sprintf(
                    __('Metadata for database %s'),
                    $db
                )
            );
        }

        $comment .= $this->exportComment();

        if (! $this->export->outputHandler($comment)) {
            return false;
        }

        foreach ($types as $type => $dbNameColumn) {
            if (! in_array($type, $metadataTypes) || ! isset($relationParams[$type])) {
                continue;
            }

            // special case, designer pages and their coordinates
            if ($type === 'pdf_pages') {
                if ($relationParameters->pdfFeature === null) {
                    continue;
                }

                $sqlQuery = 'SELECT `page_nr`, `page_descr` FROM '
                    . Util::backquote($relationParameters->pdfFeature->database)
                    . '.' . Util::backquote($relationParameters->pdfFeature->pdfPages)
                    . ' WHERE `db_name` = \'' . $dbi->escapeString($db) . "'";

                $result = $dbi->fetchResult($sqlQuery, 'page_nr', 'page_descr');

                foreach (array_keys($result) as $page) {
                    // insert row for pdf_page
                    $sqlQueryRow = 'SELECT `db_name`, `page_descr` FROM '
                        . Util::backquote($relationParameters->pdfFeature->database)
                        . '.' . Util::backquote($relationParameters->pdfFeature->pdfPages)
                        . ' WHERE `db_name` = \'' . $dbi->escapeString($db) . "'"
                        . " AND `page_nr` = '" . intval($page) . "'";

                    if (
                        ! $this->exportData(
                            $relationParameters->pdfFeature->database->getName(),
                            $relationParameters->pdfFeature->pdfPages->getName(),
                            $GLOBALS['crlf'],
                            '',
                            $sqlQueryRow,
                            $aliases
                        )
                    ) {
                        return false;
                    }

                    $lastPage = $GLOBALS['crlf']
                        . 'SET @LAST_PAGE = LAST_INSERT_ID();'
                        . $GLOBALS['crlf'];
                    if (! $this->export->outputHandler($lastPage)) {
                        return false;
                    }

                    $sqlQueryCoords = 'SELECT `db_name`, `table_name`, '
                        . "'@LAST_PAGE' AS `pdf_page_number`, `x`, `y` FROM "
                        . Util::backquote($relationParameters->pdfFeature->database)
                        . '.' . Util::backquote($relationParameters->pdfFeature->tableCoords)
                        . " WHERE `pdf_page_number` = '" . $page . "'";

                    $GLOBALS['exporting_metadata'] = true;
                    if (
                        ! $this->exportData(
                            $relationParameters->pdfFeature->database->getName(),
                            $relationParameters->pdfFeature->tableCoords->getName(),
                            $GLOBALS['crlf'],
                            '',
                            $sqlQueryCoords,
                            $aliases
                        )
                    ) {
                        $GLOBALS['exporting_metadata'] = false;

                        return false;
                    }

                    $GLOBALS['exporting_metadata'] = false;
                }

                continue;
            }

            // remove auto_incrementing id field for some tables
            if ($type === 'bookmark') {
                $sqlQuery = 'SELECT `dbase`, `user`, `label`, `query` FROM ';
            } elseif ($type === 'column_info') {
                $sqlQuery = 'SELECT `db_name`, `table_name`, `column_name`,'
                    . ' `comment`, `mimetype`, `transformation`,'
                    . ' `transformation_options`, `input_transformation`,'
                    . ' `input_transformation_options` FROM';
            } elseif ($type === 'savedsearches') {
                $sqlQuery = 'SELECT `username`, `db_name`, `search_name`, `search_data` FROM';
            } else {
                $sqlQuery = 'SELECT * FROM ';
            }

            $sqlQuery .= Util::backquote($relationParameters->db)
                . '.' . Util::backquote((string) $relationParams[$type])
                . ' WHERE ' . Util::backquote($dbNameColumn)
                . " = '" . $dbi->escapeString($db) . "'";
            if (isset($table)) {
                $sqlQuery .= " AND `table_name` = '"
                    . $dbi->escapeString($table) . "'";
            }

            if (
                ! $this->exportData(
                    (string) $relationParameters->db,
                    (string) $relationParams[$type],
                    $GLOBALS['crlf'],
                    '',
                    $sqlQuery,
                    $aliases
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a stand-in CREATE definition to resolve view dependencies
     *
     * @param string $db      the database name
     * @param string $view    the view name
     * @param string $crlf    the end of line sequence
     * @param array  $aliases Aliases of db/table/columns
     *
     * @return string resulting definition
     */
    public function getTableDefStandIn($db, $view, $crlf, $aliases = [])
    {
        global $dbi;

        $dbAlias = $db;
        $viewAlias = $view;
        $this->initAlias($aliases, $dbAlias, $viewAlias);
        $createQuery = '';
        if (! empty($GLOBALS['sql_drop_table'])) {
            $createQuery .= 'DROP VIEW IF EXISTS '
                . Util::backquote($viewAlias)
                . ';' . $crlf;
        }

        $createQuery .= 'CREATE TABLE ';

        if (isset($GLOBALS['sql_if_not_exists']) && $GLOBALS['sql_if_not_exists']) {
            $createQuery .= 'IF NOT EXISTS ';
        }

        $createQuery .= Util::backquote($viewAlias) . ' (' . $crlf;
        $tmp = [];
        $columns = $dbi->getColumnsFull($db, $view);
        foreach ($columns as $columnName => $definition) {
            $colAlias = $columnName;
            if (! empty($aliases[$db]['tables'][$view]['columns'][$colAlias])) {
                $colAlias = $aliases[$db]['tables'][$view]['columns'][$colAlias];
            }

            $tmp[] = Util::backquote($colAlias) . ' ' .
                $definition['Type'] . $crlf;
        }

        return $createQuery . implode(',', $tmp) . ');' . $crlf;
    }

    /**
     * Returns CREATE definition that matches $view's structure
     *
     * @param string $db           the database name
     * @param string $view         the view name
     * @param string $crlf         the end of line sequence
     * @param bool   $addSemicolon whether to add semicolon and end-of-line at
     *                              the end
     * @param array  $aliases      Aliases of db/table/columns
     *
     * @return string resulting schema
     */
    private function getTableDefForView(
        $db,
        $view,
        $crlf,
        $addSemicolon = true,
        array $aliases = []
    ) {
        global $dbi;

        $dbAlias = $db;
        $viewAlias = $view;
        $this->initAlias($aliases, $dbAlias, $viewAlias);
        $createQuery = 'CREATE TABLE';
        if (isset($GLOBALS['sql_if_not_exists'])) {
            $createQuery .= ' IF NOT EXISTS ';
        }

        $createQuery .= Util::backquote($viewAlias) . '(' . $crlf;

        $columns = $dbi->getColumns($db, $view, true);

        $firstCol = true;
        foreach ($columns as $column) {
            $colAlias = $column['Field'];
            if (! empty($aliases[$db]['tables'][$view]['columns'][$colAlias])) {
                $colAlias = $aliases[$db]['tables'][$view]['columns'][$colAlias];
            }

            $extractedColumnspec = Util::extractColumnSpec($column['Type']);

            if (! $firstCol) {
                $createQuery .= ',' . $crlf;
            }

            $createQuery .= '    ' . Util::backquote($colAlias);
            $createQuery .= ' ' . $column['Type'];
            if ($extractedColumnspec['can_contain_collation'] && ! empty($column['Collation'])) {
                $createQuery .= ' COLLATE ' . $column['Collation'];
            }

            if ($column['Null'] === 'NO') {
                $createQuery .= ' NOT NULL';
            }

            if (isset($column['Default'])) {
                $createQuery .= " DEFAULT '"
                    . $dbi->escapeString($column['Default']) . "'";
            } else {
                if ($column['Null'] === 'YES') {
                    $createQuery .= ' DEFAULT NULL';
                }
            }

            if (! empty($column['Comment'])) {
                $createQuery .= " COMMENT '"
                    . $dbi->escapeString($column['Comment']) . "'";
            }

            $firstCol = false;
        }

        $createQuery .= $crlf . ')' . ($addSemicolon ? ';' : '') . $crlf;

        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }

        if ($compat === 'MSSQL') {
            $createQuery = $this->makeCreateTableMSSQLCompatible($createQuery);
        }

        return $createQuery;
    }

    /**
     * Returns $table's CREATE definition
     *
     * @param string $db                      the database name
     * @param string $table                   the table name
     * @param string $crlf                    the end of line sequence
     * @param string $errorUrl                the url to go back in case
     *                                         of error
     * @param bool   $showDates               whether to include creation/
     *                                         update/check dates
     * @param bool   $addSemicolon            whether to add semicolon and
     *                                         end-of-line at the end
     * @param bool   $view                    whether we're handling a view
     * @param bool   $updateIndexesIncrements whether we need to update
     *                                          two global variables
     * @param array  $aliases                 Aliases of db/table/columns
     *
     * @return string resulting schema
     */
    public function getTableDef(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $showDates = false,
        $addSemicolon = true,
        $view = false,
        $updateIndexesIncrements = true,
        array $aliases = []
    ) {
        global $sql_drop_table, $sql_backquotes, $sql_constraints,
               $sql_constraints_query, $sql_indexes, $sql_indexes_query,
               $sql_auto_increments, $sql_drop_foreign_keys, $dbi, $cfg;

        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        $schemaCreate = '';
        $newCrlf = $crlf;

        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }

        $result = $dbi->tryQuery(
            'SHOW TABLE STATUS FROM ' . Util::backquote($db)
            . ' WHERE Name = \'' . $dbi->escapeString((string) $table) . '\''
        );
        if ($result != false) {
            if ($result->numRows() > 0) {
                $tmpres = $result->fetchAssoc();

                if ($showDates && isset($tmpres['Create_time']) && ! empty($tmpres['Create_time'])) {
                    $schemaCreate .= $this->exportComment(
                        __('Creation:') . ' '
                        . Util::localisedDate(
                            strtotime($tmpres['Create_time'])
                        )
                    );
                    $newCrlf = $this->exportComment() . $crlf;
                }

                if ($showDates && isset($tmpres['Update_time']) && ! empty($tmpres['Update_time'])) {
                    $schemaCreate .= $this->exportComment(
                        __('Last update:') . ' '
                        . Util::localisedDate(
                            strtotime($tmpres['Update_time'])
                        )
                    );
                    $newCrlf = $this->exportComment() . $crlf;
                }

                if ($showDates && isset($tmpres['Check_time']) && ! empty($tmpres['Check_time'])) {
                    $schemaCreate .= $this->exportComment(
                        __('Last check:') . ' '
                        . Util::localisedDate(
                            strtotime($tmpres['Check_time'])
                        )
                    );
                    $newCrlf = $this->exportComment() . $crlf;
                }
            }
        }

        $schemaCreate .= $newCrlf;

        if (! empty($sql_drop_table) && $dbi->getTable($db, $table)->isView()) {
            $schemaCreate .= 'DROP VIEW IF EXISTS '
                . Util::backquoteCompat($tableAlias, 'NONE', $sql_backquotes) . ';'
                . $crlf;
        }

        // no need to generate a DROP VIEW here, it was done earlier
        if (! empty($sql_drop_table) && ! $dbi->getTable($db, $table)->isView()) {
            $schemaCreate .= 'DROP TABLE IF EXISTS '
                . Util::backquoteCompat($tableAlias, 'NONE', $sql_backquotes) . ';'
                . $crlf;
        }

        // Complete table dump,
        // Whether to quote table and column names or not
        if ($sql_backquotes) {
            $dbi->query('SET SQL_QUOTE_SHOW_CREATE = 1');
        } else {
            $dbi->query('SET SQL_QUOTE_SHOW_CREATE = 0');
        }

        // I don't see the reason why this unbuffered query could cause problems,
        // because SHOW CREATE TABLE returns only one row, and we free the
        // results below. Nonetheless, we got 2 user reports about this
        // (see bug 1562533) so I removed the unbuffered mode.
        // $result = $dbi->query('SHOW CREATE TABLE ' . backquote($db)
        // . '.' . backquote($table), null, DatabaseInterface::QUERY_UNBUFFERED);
        //
        // Note: SHOW CREATE TABLE, at least in MySQL 5.1.23, does not
        // produce a displayable result for the default value of a BIT
        // column, nor does the mysqldump command. See MySQL bug 35796
        $dbi->tryQuery('USE ' . Util::backquote($db));
        $result = $dbi->tryQuery(
            'SHOW CREATE TABLE ' . Util::backquote($db) . '.'
            . Util::backquote($table)
        );
        // an error can happen, for example the table is crashed
        $tmpError = $dbi->getError();
        if ($tmpError) {
            $message = sprintf(__('Error reading structure for table %s:'), $db . '.' . $table);
            $message .= ' ' . $tmpError;
            if (! defined('TESTSUITE')) {
                trigger_error($message, E_USER_ERROR);
            }

            return $this->exportComment($message);
        }

        // Old mode is stored so it can be restored once exporting is done.
        $oldMode = Context::$MODE;

        $warning = '';

        $row = null;
        if ($result !== false) {
            $row = $result->fetchRow();
        }

        if ($row) {
            $createQuery = $row[1];
            unset($row);

            // Convert end of line chars to one that we want (note that MySQL
            // doesn't return query it will accept in all cases)
            if (mb_strpos($createQuery, "(\r\n ")) {
                $createQuery = str_replace("\r\n", $crlf, $createQuery);
            } elseif (mb_strpos($createQuery, "(\n ")) {
                $createQuery = str_replace("\n", $crlf, $createQuery);
            } elseif (mb_strpos($createQuery, "(\r ")) {
                $createQuery = str_replace("\r", $crlf, $createQuery);
            }

            /*
             * Drop database name from VIEW creation.
             *
             * This is a bit tricky, but we need to issue SHOW CREATE TABLE with
             * database name, but we don't want name to show up in CREATE VIEW
             * statement.
             */
            if ($view) {
                //TODO: use parser
                $createQuery = preg_replace(
                    '/' . preg_quote(Util::backquote($db), '/') . '\./',
                    '',
                    $createQuery
                );
                $parser = new Parser($createQuery);
                /**
                 * `CREATE TABLE` statement.
                 *
                 * @var CreateStatement
                 */
                $statement = $parser->statements[0];

                // exclude definition of current user
                if ($cfg['Export']['remove_definer_from_definitions'] || isset($GLOBALS['sql_view_current_user'])) {
                    $statement->options->remove('DEFINER');
                }

                if (isset($GLOBALS['sql_simple_view_export'])) {
                    $statement->options->remove('SQL SECURITY');
                    $statement->options->remove('INVOKER');
                    $statement->options->remove('ALGORITHM');
                    $statement->options->remove('DEFINER');
                }

                $createQuery = $statement->build();

                // whether to replace existing view or not
                if (isset($GLOBALS['sql_or_replace_view'])) {
                    $createQuery = preg_replace('/^CREATE/', 'CREATE OR REPLACE', $createQuery);
                }
            }

            // Substitute aliases in `CREATE` query.
            $createQuery = $this->replaceWithAliases(null, $createQuery, $aliases, $db, $table, $flag);

            // One warning per view.
            if ($flag && $view) {
                $warning = $this->exportComment()
                    . $this->exportComment(
                        __('It appears your database uses views;')
                    )
                    . $this->exportComment(
                        __('alias export may not work reliably in all cases.')
                    )
                    . $this->exportComment();
            }

            // Adding IF NOT EXISTS, if required.
            if (isset($GLOBALS['sql_if_not_exists'])) {
                $createQuery = (string) preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $createQuery);
            }

            // Making the query MSSQL compatible.
            if ($compat === 'MSSQL') {
                $createQuery = $this->makeCreateTableMSSQLCompatible($createQuery);
            }

            // Views have no constraints, indexes, etc. They do not require any
            // analysis.
            if (! $view) {
                if (empty($sql_backquotes)) {
                    // Option "Enclose table and column names with backquotes"
                    // was checked.
                    Context::$MODE |= Context::SQL_MODE_NO_ENCLOSING_QUOTES;
                }

                // Using appropriate quotes.
                if (($compat === 'MSSQL') || ($sql_backquotes === '"')) {
                    Context::$MODE |= Context::SQL_MODE_ANSI_QUOTES;
                }
            }

            /**
             * Parser used for analysis.
             */
            $parser = new Parser($createQuery);

            /**
             * `CREATE TABLE` statement.
             *
             * @var CreateStatement
             */
            $statement = $parser->statements[0];

            if (! empty($statement->entityOptions)) {
                $engine = $statement->entityOptions->has('ENGINE');
            } else {
                $engine = '';
            }

            /* Avoid operation on ARCHIVE tables as those can not be altered */
            if (
                (! empty($statement->fields) && is_array($statement->fields))
                && (empty($engine) || strtoupper($engine) !== 'ARCHIVE')
            ) {

                /**
                 * Fragments containing definition of each constraint.
                 *
                 * @var array
                 */
                $constraints = [];

                /**
                 * Fragments containing definition of each index.
                 *
                 * @var array
                 */
                $indexes = [];

                /**
                 * Fragments containing definition of each FULLTEXT index.
                 *
                 * @var array
                 */
                $indexesFulltext = [];

                /**
                 * Fragments containing definition of each foreign key that will
                 * be dropped.
                 *
                 * @var array
                 */
                $dropped = [];

                /**
                 * Fragment containing definition of the `AUTO_INCREMENT`.
                 *
                 * @var array
                 */
                $autoIncrement = [];

                // Scanning each field of the `CREATE` statement to fill the arrays
                // above.
                // If the field is used in any of the arrays above, it is removed
                // from the original definition.
                // Also, AUTO_INCREMENT attribute is removed.
                /** @var CreateDefinition $field */
                foreach ($statement->fields as $key => $field) {
                    if ($field->isConstraint) {
                        // Creating the parts that add constraints.
                        $constraints[] = $field::build($field);
                        unset($statement->fields[$key]);
                    } elseif (! empty($field->key)) {
                        // Creating the parts that add indexes (must not be
                        // constraints).
                        if ($field->key->type === 'FULLTEXT KEY') {
                            $indexesFulltext[] = $field::build($field);
                            unset($statement->fields[$key]);
                        } else {
                            if (empty($GLOBALS['sql_if_not_exists'])) {
                                $indexes[] = str_replace(
                                    'COMMENT=\'',
                                    'COMMENT \'',
                                    $field::build($field)
                                );
                                unset($statement->fields[$key]);
                            }
                        }
                    }

                    // Creating the parts that drop foreign keys.
                    if (! empty($field->key)) {
                        if ($field->key->type === 'FOREIGN KEY') {
                            $dropped[] = 'FOREIGN KEY ' . Context::escape($field->name);
                            unset($statement->fields[$key]);
                        }
                    }

                    // Dropping AUTO_INCREMENT.
                    if (empty($field->options)) {
                        continue;
                    }

                    if (! $field->options->has('AUTO_INCREMENT') || ! empty($GLOBALS['sql_if_not_exists'])) {
                        continue;
                    }

                    $autoIncrement[] = $field::build($field);
                    $field->options->remove('AUTO_INCREMENT');
                }

                /**
                 * The header of the `ALTER` statement (`ALTER TABLE tbl`).
                 *
                 * @var string
                 */
                $alterHeader = 'ALTER TABLE ' .
                    Util::backquoteCompat($tableAlias, $compat, $sql_backquotes);

                /**
                 * The footer of the `ALTER` statement (usually ';')
                 *
                 * @var string
                 */
                $alterFooter = ';' . $crlf;

                // Generating constraints-related query.
                if (! empty($constraints)) {
                    $sql_constraints_query = $alterHeader . $crlf . '  ADD '
                        . implode(',' . $crlf . '  ADD ', $constraints)
                        . $alterFooter;

                    $sql_constraints = $this->generateComment(
                        $crlf,
                        $sql_constraints,
                        __('Constraints for dumped tables'),
                        __('Constraints for table'),
                        $tableAlias,
                        $compat
                    ) . $sql_constraints_query;
                }

                // Generating indexes-related query.
                $sql_indexes_query = '';

                if (! empty($indexes)) {
                    $sql_indexes_query .= $alterHeader . $crlf . '  ADD '
                        . implode(',' . $crlf . '  ADD ', $indexes)
                        . $alterFooter;
                }

                if (! empty($indexesFulltext)) {
                    // InnoDB supports one FULLTEXT index creation at a time.
                    // So FULLTEXT indexes are created one-by-one after other
                    // indexes where created.
                    $sql_indexes_query .= $alterHeader .
                        ' ADD ' . implode($alterFooter . $alterHeader . ' ADD ', $indexesFulltext) . $alterFooter;
                }

                if (! empty($indexes) || ! empty($indexesFulltext)) {
                    $sql_indexes = $this->generateComment(
                        $crlf,
                        $sql_indexes,
                        __('Indexes for dumped tables'),
                        __('Indexes for table'),
                        $tableAlias,
                        $compat
                    ) . $sql_indexes_query;
                }

                // Generating drop foreign keys-related query.
                if (! empty($dropped)) {
                    $sql_drop_foreign_keys = $alterHeader . $crlf . '  DROP '
                        . implode(',' . $crlf . '  DROP ', $dropped)
                        . $alterFooter;
                }

                // Generating auto-increment-related query.
                if ($autoIncrement !== [] && $updateIndexesIncrements) {
                    $sqlAutoIncrementsQuery = $alterHeader . $crlf . '  MODIFY '
                        . implode(',' . $crlf . '  MODIFY ', $autoIncrement);
                    if (
                        isset($GLOBALS['sql_auto_increment'])
                        && ($statement->entityOptions->has('AUTO_INCREMENT') !== false)
                    ) {
                        if (
                            ! isset($GLOBALS['table_data'])
                            || (isset($GLOBALS['table_data'])
                            && in_array($table, $GLOBALS['table_data']))
                        ) {
                            $sqlAutoIncrementsQuery .= ', AUTO_INCREMENT='
                                . $statement->entityOptions->has('AUTO_INCREMENT');
                        }
                    }

                    $sqlAutoIncrementsQuery .= ';' . $crlf;

                    $sql_auto_increments = $this->generateComment(
                        $crlf,
                        $sql_auto_increments,
                        __('AUTO_INCREMENT for dumped tables'),
                        __('AUTO_INCREMENT for table'),
                        $tableAlias,
                        $compat
                    ) . $sqlAutoIncrementsQuery;
                }

                // Removing the `AUTO_INCREMENT` attribute from the `CREATE TABLE`
                // too.
                if (
                    ! empty($statement->entityOptions)
                    && (empty($GLOBALS['sql_if_not_exists'])
                    || empty($GLOBALS['sql_auto_increment']))
                ) {
                    $statement->entityOptions->remove('AUTO_INCREMENT');
                }

                // Rebuilding the query.
                $createQuery = $statement->build();
            }

            $schemaCreate .= $createQuery;
        }

        // Restoring old mode.
        Context::$MODE = $oldMode;

        return $warning . $schemaCreate . ($addSemicolon ? ';' . $crlf : '');
    }

    /**
     * Returns $table's comments, relations etc.
     *
     * @param string $db         database name
     * @param string $table      table name
     * @param bool   $doRelation whether to include relation comments
     * @param bool   $doMime     whether to include mime comments
     * @param array  $aliases    Aliases of db/table/columns
     *
     * @return string resulting comments
     */
    private function getTableComments(
        $db,
        $table,
        $doRelation = false,
        $doMime = false,
        array $aliases = []
    ) {
        global $sql_backquotes;

        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        $relationParameters = $this->relation->getRelationParameters();

        $schemaCreate = '';

        // Check if we can use Relations
        [$resRel, $haveRel] = $this->relation->getRelationsAndStatus(
            $doRelation && $relationParameters->relationFeature !== null,
            $db,
            $table
        );

        if ($doMime && $relationParameters->browserTransformationFeature !== null) {
            $mimeMap = $this->transformations->getMime($db, $table, true);
            if ($mimeMap === null) {
                unset($mimeMap);
            }
        }

        if (isset($mimeMap) && count($mimeMap) > 0) {
            $schemaCreate .= $this->possibleCRLF()
                . $this->exportComment()
                . $this->exportComment(
                    __('MEDIA TYPES FOR TABLE') . ' '
                    . Util::backquoteCompat($table, 'NONE', $sql_backquotes) . ':'
                );
            foreach ($mimeMap as $mimeField => $mime) {
                $schemaCreate .= $this->exportComment(
                    '  '
                    . Util::backquoteCompat($mimeField, 'NONE', $sql_backquotes)
                )
                . $this->exportComment(
                    '      '
                    . Util::backquoteCompat(
                        $mime['mimetype'],
                        'NONE',
                        $sql_backquotes
                    )
                );
            }

            $schemaCreate .= $this->exportComment();
        }

        if ($haveRel) {
            $schemaCreate .= $this->possibleCRLF()
                . $this->exportComment()
                . $this->exportComment(
                    __('RELATIONSHIPS FOR TABLE') . ' '
                    . Util::backquoteCompat($tableAlias, 'NONE', $sql_backquotes)
                    . ':'
                );

            foreach ($resRel as $relField => $rel) {
                if ($relField !== 'foreign_keys_data') {
                    $relFieldAlias = ! empty(
                        $aliases[$db]['tables'][$table]['columns'][$relField]
                    ) ? $aliases[$db]['tables'][$table]['columns'][$relField]
                        : $relField;
                    $schemaCreate .= $this->exportComment(
                        '  '
                        . Util::backquoteCompat(
                            $relFieldAlias,
                            'NONE',
                            $sql_backquotes
                        )
                    )
                    . $this->exportComment(
                        '      '
                        . Util::backquoteCompat(
                            $rel['foreign_table'],
                            'NONE',
                            $sql_backquotes
                        )
                        . ' -> '
                        . Util::backquoteCompat(
                            $rel['foreign_field'],
                            'NONE',
                            $sql_backquotes
                        )
                    );
                } else {
                    foreach ($rel as $oneKey) {
                        foreach ($oneKey['index_list'] as $index => $field) {
                            $relFieldAlias = ! empty(
                                $aliases[$db]['tables'][$table]['columns'][$field]
                            ) ? $aliases[$db]['tables'][$table]['columns'][$field]
                                : $field;
                            $schemaCreate .= $this->exportComment(
                                '  '
                                . Util::backquoteCompat(
                                    $relFieldAlias,
                                    'NONE',
                                    $sql_backquotes
                                )
                            )
                            . $this->exportComment(
                                '      '
                                . Util::backquoteCompat(
                                    $oneKey['ref_table_name'],
                                    'NONE',
                                    $sql_backquotes
                                )
                                . ' -> '
                                . Util::backquoteCompat(
                                    $oneKey['ref_index_list'][$index],
                                    'NONE',
                                    $sql_backquotes
                                )
                            );
                        }
                    }
                }
            }

            $schemaCreate .= $this->exportComment();
        }

        return $schemaCreate;
    }

    /**
     * Outputs a raw query
     *
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     * @param string      $crlf     the end of line sequence
     */
    public function exportRawQuery(string $errorUrl, ?string $db, string $sqlQuery, string $crlf): bool
    {
        global $dbi;

        if ($db !== null) {
            $dbi->selectDb($db);
        }

        return $this->exportData($db ?? '', '', $crlf, $errorUrl, $sqlQuery);
    }

    /**
     * Outputs table's structure
     *
     * @param string $db         database name
     * @param string $table      table name
     * @param string $crlf       the end of line sequence
     * @param string $errorUrl   the url to go back in case of error
     * @param string $exportMode 'create_table','triggers','create_view',
     *                            'stand_in'
     * @param string $exportType 'server', 'database', 'table'
     * @param bool   $relation   whether to include relation comments
     * @param bool   $comments   whether to include the pmadb-style column
     *                           comments as comments in the structure; this is
     *                           deprecated but the parameter is left here
     *                           because /export calls exportStructure()
     *                           also for other export types which use this
     *                           parameter
     * @param bool   $mime       whether to include mime comments
     * @param bool   $dates      whether to include creation/update/check dates
     * @param array  $aliases    Aliases of db/table/columns
     */
    public function exportStructure(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $exportMode,
        $exportType,
        $relation = false,
        $comments = false,
        $mime = false,
        $dates = false,
        array $aliases = []
    ): bool {
        global $dbi;

        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);
        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }

        $formattedTableName = Util::backquoteCompat($tableAlias, $compat, isset($GLOBALS['sql_backquotes']));
        $dump = $this->possibleCRLF()
            . $this->exportComment(str_repeat('-', 56))
            . $this->possibleCRLF()
            . $this->exportComment();

        switch ($exportMode) {
            case 'create_table':
                $dump .= $this->exportComment(
                    __('Table structure for table') . ' ' . $formattedTableName
                );
                $dump .= $this->exportComment();
                $dump .= $this->getTableDef($db, $table, $crlf, $errorUrl, $dates, true, false, true, $aliases);
                $dump .= $this->getTableComments($db, $table, $relation, $mime, $aliases);
                break;
            case 'triggers':
                $dump = '';
                $delimiter = '$$';
                $triggers = $dbi->getTriggers($db, $table, $delimiter);
                if ($triggers) {
                    $dump .= $this->possibleCRLF()
                    . $this->exportComment()
                    . $this->exportComment(
                        __('Triggers') . ' ' . $formattedTableName
                    )
                        . $this->exportComment();
                    $usedAlias = false;
                    $triggerQuery = '';
                    foreach ($triggers as $trigger) {
                        if (! empty($GLOBALS['sql_drop_table'])) {
                            $triggerQuery .= $trigger['drop'] . ';' . $crlf;
                        }

                        $triggerQuery .= 'DELIMITER ' . $delimiter . $crlf;
                        $triggerQuery .= $this->replaceWithAliases(
                            $delimiter,
                            $trigger['create'],
                            $aliases,
                            $db,
                            $table,
                            $flag
                        );
                        if ($flag) {
                            $usedAlias = true;
                        }

                        $triggerQuery .= $delimiter . $crlf . 'DELIMITER ;' . $crlf;
                    }

                    // One warning per table.
                    if ($usedAlias) {
                        $dump .= $this->exportComment(
                            __('It appears your table uses triggers;')
                        )
                        . $this->exportComment(
                            __('alias export may not work reliably in all cases.')
                        )
                        . $this->exportComment();
                    }

                    $dump .= $triggerQuery;
                }

                break;
            case 'create_view':
                if (empty($GLOBALS['sql_views_as_tables'])) {
                    $dump .= $this->exportComment(
                        __('Structure for view')
                        . ' '
                        . $formattedTableName
                    )
                    . $this->exportComment();
                    // delete the stand-in table previously created (if any)
                    if ($exportType !== 'table') {
                        $dump .= 'DROP TABLE IF EXISTS '
                            . Util::backquote($tableAlias) . ';' . $crlf;
                    }

                    $dump .= $this->getTableDef($db, $table, $crlf, $errorUrl, $dates, true, true, true, $aliases);
                } else {
                    $dump .= $this->exportComment(
                        sprintf(
                            __('Structure for view %s exported as a table'),
                            $formattedTableName
                        )
                    )
                    . $this->exportComment();
                    // delete the stand-in table previously created (if any)
                    if ($exportType !== 'table') {
                        $dump .= 'DROP TABLE IF EXISTS '
                        . Util::backquote($tableAlias) . ';' . $crlf;
                    }

                    $dump .= $this->getTableDefForView($db, $table, $crlf, true, $aliases);
                }

                if (empty($GLOBALS['sql_views_as_tables'])) {
                    // Save views, to be inserted after indexes
                    // in case the view uses USE INDEX syntax
                    $this->sqlViews .= $dump;
                    $dump = '';
                }

                break;
            case 'stand_in':
                $dump .= $this->exportComment(
                    __('Stand-in structure for view') . ' ' . $formattedTableName
                )
                    . $this->exportComment(
                        __('(See below for the actual view)')
                    )
                    . $this->exportComment();
                // export a stand-in definition to resolve view dependencies
                $dump .= $this->getTableDefStandIn($db, $table, $crlf, $aliases);
        }

        // this one is built by getTableDef() to use in table copy/move
        // but not in the case of export
        unset($GLOBALS['sql_constraints_query']);

        return $this->export->outputHandler($dump);
    }

    /**
     * Outputs the content of a table in SQL format
     *
     * @param string $db       database name
     * @param string $table    table name
     * @param string $crlf     the end of line sequence
     * @param string $errorUrl the url to go back in case of error
     * @param string $sqlQuery SQL query for obtaining data
     * @param array  $aliases  Aliases of db/table/columns
     */
    public function exportData(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $sqlQuery,
        array $aliases = []
    ): bool {
        global $current_row, $sql_backquotes, $dbi;

        // Do not export data for merge tables
        if ($dbi->getTable($db, $table)->isMerge()) {
            return true;
        }

        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }

        $formattedTableName = Util::backquoteCompat($tableAlias, $compat, $sql_backquotes);

        // Do not export data for a VIEW, unless asked to export the view as a table
        // (For a VIEW, this is called only when exporting a single VIEW)
        if ($dbi->getTable($db, $table)->isView() && empty($GLOBALS['sql_views_as_tables'])) {
            $head = $this->possibleCRLF()
                . $this->exportComment()
                . $this->exportComment('VIEW ' . $formattedTableName)
                . $this->exportComment(__('Data:') . ' ' . __('None'))
                . $this->exportComment()
                . $this->possibleCRLF();

            return $this->export->outputHandler($head);
        }

        $result = $dbi->tryQuery($sqlQuery, DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED);
        // a possible error: the table has crashed
        $tmpError = $dbi->getError();
        if ($tmpError) {
            $message = sprintf(__('Error reading data for table %s:'), $db . '.' . $table);
            $message .= ' ' . $tmpError;
            if (! defined('TESTSUITE')) {
                trigger_error($message, E_USER_ERROR);
            }

            return $this->export->outputHandler(
                $this->exportComment($message)
            );
        }

        if ($result === false) {
            return true;
        }

        $fieldsCnt = $result->numFields();

        // Get field information
        /** @var FieldMetadata[] $fieldsMeta */
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $fieldSet = [];
        for ($j = 0; $j < $fieldsCnt; $j++) {
            $colAs = $fieldsMeta[$j]->name;
            if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            $fieldSet[$j] = Util::backquoteCompat($colAs, $compat, $sql_backquotes);
        }

        if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] === 'UPDATE') {
            // update
            $schemaInsert = 'UPDATE ';
            if (isset($GLOBALS['sql_ignore'])) {
                $schemaInsert .= 'IGNORE ';
            }

            // avoid EOL blank
            $schemaInsert .= Util::backquoteCompat($tableAlias, $compat, $sql_backquotes) . ' SET';
        } else {
            // insert or replace
            if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] === 'REPLACE') {
                $sqlCommand = 'REPLACE';
            } else {
                $sqlCommand = 'INSERT';
            }

            // delayed inserts?
            if (isset($GLOBALS['sql_delayed'])) {
                $insertDelayed = ' DELAYED';
            } else {
                $insertDelayed = '';
            }

            // insert ignore?
            if (isset($GLOBALS['sql_type'], $GLOBALS['sql_ignore']) && $GLOBALS['sql_type'] === 'INSERT') {
                $insertDelayed .= ' IGNORE';
            }

            //truncate table before insert
            if (isset($GLOBALS['sql_truncate']) && $GLOBALS['sql_truncate'] && $sqlCommand === 'INSERT') {
                $truncate = 'TRUNCATE TABLE '
                    . Util::backquoteCompat($tableAlias, $compat, $sql_backquotes) . ';';
                $truncatehead = $this->possibleCRLF()
                    . $this->exportComment()
                    . $this->exportComment(
                        __('Truncate table before insert') . ' '
                        . $formattedTableName
                    )
                    . $this->exportComment()
                    . $crlf;
                $this->export->outputHandler($truncatehead);
                $this->export->outputHandler($truncate);
            }

            // scheme for inserting fields
            if ($GLOBALS['sql_insert_syntax'] === 'complete' || $GLOBALS['sql_insert_syntax'] === 'both') {
                $fields = implode(', ', $fieldSet);
                $schemaInsert = $sqlCommand . $insertDelayed . ' INTO '
                    . Util::backquoteCompat($tableAlias, $compat, $sql_backquotes)
                    // avoid EOL blank
                    . ' (' . $fields . ') VALUES';
            } else {
                $schemaInsert = $sqlCommand . $insertDelayed . ' INTO '
                    . Util::backquoteCompat($tableAlias, $compat, $sql_backquotes)
                    . ' VALUES';
            }
        }

        //\x08\\x09, not required
        $current_row = 0;
        $querySize = 0;
        if (
            ($GLOBALS['sql_insert_syntax'] === 'extended'
            || $GLOBALS['sql_insert_syntax'] === 'both')
            && (! isset($GLOBALS['sql_type'])
            || $GLOBALS['sql_type'] !== 'UPDATE')
        ) {
            $separator = ',';
            $schemaInsert .= $crlf;
        } else {
            $separator = ';';
        }

        while ($row = $result->fetchRow()) {
            if ($current_row == 0) {
                $head = $this->possibleCRLF()
                    . $this->exportComment()
                    . $this->exportComment(
                        __('Dumping data for table') . ' '
                        . $formattedTableName
                    )
                    . $this->exportComment()
                    . $crlf;
                if (! $this->export->outputHandler($head)) {
                    return false;
                }
            }

            // We need to SET IDENTITY_INSERT ON for MSSQL
            if (
                isset($GLOBALS['sql_compatibility'])
                && $GLOBALS['sql_compatibility'] === 'MSSQL'
                && $current_row == 0
            ) {
                if (
                    ! $this->export->outputHandler(
                        'SET IDENTITY_INSERT '
                        . Util::backquoteCompat(
                            $tableAlias,
                            $compat,
                            $sql_backquotes
                        )
                        . ' ON ;' . $crlf
                    )
                ) {
                    return false;
                }
            }

            $current_row++;
            $values = [];
            for ($j = 0; $j < $fieldsCnt; $j++) {
                // NULL
                if (! isset($row[$j])) {
                    $values[] = 'NULL';
                } elseif (
                    $fieldsMeta[$j]->isNumeric
                ) {
                    // a number
                    $values[] = $row[$j];
                } elseif ($fieldsMeta[$j]->isBinary && isset($GLOBALS['sql_hex_for_binary'])) {
                    // a true BLOB
                    // - mysqldump only generates hex data when the --hex-blob
                    //   option is used, for fields having the binary attribute
                    //   no hex is generated
                    // - a TEXT field returns type blob but a real blob
                    //   returns also the 'binary' flag

                    // empty blobs need to be different, but '0' is also empty
                    // :-(
                    if (empty($row[$j]) && $row[$j] != '0') {
                        $values[] = '\'\'';
                    } else {
                        $values[] = '0x' . bin2hex($row[$j]);
                    }
                } elseif ($fieldsMeta[$j]->isMappedTypeBit) {
                    // detection of 'bit' works only on mysqli extension
                    $values[] = "b'" . Util::printableBitValue(
                        (int) $row[$j],
                        (int) $fieldsMeta[$j]->length
                    ) . "'";
                } elseif ($fieldsMeta[$j]->isMappedTypeGeometry) {
                    // export GIS types as hex
                    $values[] = '0x' . bin2hex($row[$j]);
                } elseif (! empty($GLOBALS['exporting_metadata']) && $row[$j] === '@LAST_PAGE') {
                    $values[] = '@LAST_PAGE';
                } elseif ($row[$j] === '') {
                    $values[] = "''";
                } else {
                    // something else -> treat as a string
                    $values[] = '\'' . $dbi->escapeString($row[$j]) . '\'';
                }
            }

            // should we make update?
            if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] === 'UPDATE') {
                $insertLine = $schemaInsert;
                for ($i = 0; $i < $fieldsCnt; $i++) {
                    if ($i == 0) {
                        $insertLine .= ' ';
                    }

                    if ($i > 0) {
                        // avoid EOL blank
                        $insertLine .= ',';
                    }

                    $insertLine .= $fieldSet[$i] . ' = ' . $values[$i];
                }

                [$tmpUniqueCondition, $tmpClauseIsUnique] = Util::getUniqueCondition(
                    $fieldsCnt,
                    $fieldsMeta,
                    $row
                );
                $insertLine .= ' WHERE ' . $tmpUniqueCondition;
                unset($tmpUniqueCondition, $tmpClauseIsUnique);
            } else {
                // Extended inserts case
                if ($GLOBALS['sql_insert_syntax'] === 'extended' || $GLOBALS['sql_insert_syntax'] === 'both') {
                    if ($current_row == 1) {
                        $insertLine = $schemaInsert . '('
                            . implode(', ', $values) . ')';
                    } else {
                        $insertLine = '(' . implode(', ', $values) . ')';
                        $insertLineSize = mb_strlen($insertLine);
                        $sqlMaxSize = $GLOBALS['sql_max_query_size'];
                        if (isset($sqlMaxSize) && $sqlMaxSize > 0 && $querySize + $insertLineSize > $sqlMaxSize) {
                            if (! $this->export->outputHandler(';' . $crlf)) {
                                return false;
                            }

                            $querySize = 0;
                            $current_row = 1;
                            $insertLine = $schemaInsert . $insertLine;
                        }
                    }

                    $querySize += mb_strlen($insertLine);
                    // Other inserts case
                } else {
                    $insertLine = $schemaInsert
                        . '(' . implode(', ', $values) . ')';
                }
            }

            unset($values);

            if (! $this->export->outputHandler(($current_row == 1 ? '' : $separator . $crlf) . $insertLine)) {
                return false;
            }
        }

        if ($current_row > 0) {
            if (! $this->export->outputHandler(';' . $crlf)) {
                return false;
            }
        }

        // We need to SET IDENTITY_INSERT OFF for MSSQL
        if (isset($GLOBALS['sql_compatibility']) && $GLOBALS['sql_compatibility'] === 'MSSQL' && $current_row > 0) {
            $outputSucceeded = $this->export->outputHandler(
                $crlf . 'SET IDENTITY_INSERT '
                . Util::backquoteCompat(
                    $tableAlias,
                    $compat,
                    $sql_backquotes
                )
                . ' OFF;' . $crlf
            );
            if (! $outputSucceeded) {
                return false;
            }
        }

        return true;
    }

    /**
     * Make a create table statement compatible with MSSQL
     *
     * @param string $createQuery MySQL create table statement
     *
     * @return string MSSQL compatible create table statement
     */
    private function makeCreateTableMSSQLCompatible($createQuery)
    {
        // In MSSQL
        // 1. No 'IF NOT EXISTS' in CREATE TABLE
        // 2. DATE field doesn't exists, we will use DATETIME instead
        // 3. UNSIGNED attribute doesn't exist
        // 4. No length on INT, TINYINT, SMALLINT, BIGINT and no precision on
        //    FLOAT fields
        // 5. No KEY and INDEX inside CREATE TABLE
        // 6. DOUBLE field doesn't exists, we will use FLOAT instead

        $createQuery = (string) preg_replace('/^CREATE TABLE IF NOT EXISTS/', 'CREATE TABLE', (string) $createQuery);
        // first we need  to replace all lines ended with '" DATE ...,\n'
        // last preg_replace preserve us from situation with date text
        // inside DEFAULT field value
        $createQuery = (string) preg_replace(
            "/\" date DEFAULT NULL(,)?\n/",
            '" datetime DEFAULT NULL$1' . "\n",
            $createQuery
        );
        $createQuery = (string) preg_replace("/\" date NOT NULL(,)?\n/", '" datetime NOT NULL$1' . "\n", $createQuery);
        $createQuery = (string) preg_replace(
            '/" date NOT NULL DEFAULT \'([^\'])/',
            '" datetime NOT NULL DEFAULT \'$1',
            $createQuery
        );

        // next we need to replace all lines ended with ') UNSIGNED ...,'
        // last preg_replace preserve us from situation with unsigned text
        // inside DEFAULT field value
        $createQuery = (string) preg_replace("/\) unsigned NOT NULL(,)?\n/", ') NOT NULL$1' . "\n", $createQuery);
        $createQuery = (string) preg_replace(
            "/\) unsigned DEFAULT NULL(,)?\n/",
            ') DEFAULT NULL$1' . "\n",
            $createQuery
        );
        $createQuery = (string) preg_replace(
            '/\) unsigned NOT NULL DEFAULT \'([^\'])/',
            ') NOT NULL DEFAULT \'$1',
            $createQuery
        );

        // we need to replace all lines ended with
        // '" INT|TINYINT([0-9]{1,}) ...,' last preg_replace preserve us
        // from situation with int([0-9]{1,}) text inside DEFAULT field
        // value
        $createQuery = (string) preg_replace(
            '/" (int|tinyint|smallint|bigint)\([0-9]+\) DEFAULT NULL(,)?\n/',
            '" $1 DEFAULT NULL$2' . "\n",
            $createQuery
        );
        $createQuery = (string) preg_replace(
            '/" (int|tinyint|smallint|bigint)\([0-9]+\) NOT NULL(,)?\n/',
            '" $1 NOT NULL$2' . "\n",
            $createQuery
        );
        $createQuery = (string) preg_replace(
            '/" (int|tinyint|smallint|bigint)\([0-9]+\) NOT NULL DEFAULT \'([^\'])/',
            '" $1 NOT NULL DEFAULT \'$2',
            $createQuery
        );

        // we need to replace all lines ended with
        // '" FLOAT|DOUBLE([0-9,]{1,}) ...,'
        // last preg_replace preserve us from situation with
        // float([0-9,]{1,}) text inside DEFAULT field value
        $createQuery = (string) preg_replace(
            '/" (float|double)(\([0-9]+,[0-9,]+\))? DEFAULT NULL(,)?\n/',
            '" float DEFAULT NULL$3' . "\n",
            $createQuery
        );
        $createQuery = (string) preg_replace(
            '/" (float|double)(\([0-9,]+,[0-9,]+\))? NOT NULL(,)?\n/',
            '" float NOT NULL$3' . "\n",
            $createQuery
        );

        return (string) preg_replace(
            '/" (float|double)(\([0-9,]+,[0-9,]+\))? NOT NULL DEFAULT \'([^\'])/',
            '" float NOT NULL DEFAULT \'$3',
            $createQuery
        );

        // @todo remove indexes from CREATE TABLE
    }

    /**
     * replaces db/table/column names with their aliases
     *
     * @param string|null $delimiter The delimiter for the parser (";" or "$$")
     * @param string      $sqlQuery  SQL query in which aliases are to be substituted
     * @param array       $aliases   Alias information for db/table/column
     * @param string      $db        the database name
     * @param string      $table     the tablename
     * @param string      $flag      the flag denoting whether any replacement was done
     *
     * @return string query replaced with aliases
     */
    public function replaceWithAliases(
        ?string $delimiter,
        $sqlQuery,
        array $aliases,
        $db,
        $table = '',
        &$flag = null
    ) {
        $flag = false;

        /**
         * The parser of this query.
         */
        $parser = new Parser(empty($delimiter) ? $sqlQuery : 'DELIMITER ' . $delimiter . "\n" . $sqlQuery);

        if (empty($parser->statements[0])) {
            return $sqlQuery;
        }

        /**
         * The statement that represents the query.
         *
         * @var CreateStatement $statement
         */
        $statement = $parser->statements[0];

        /**
         * Old database name.
         */
        $oldDatabase = $db;

        // Replacing aliases in `CREATE TABLE` statement.
        if ($statement->options->has('TABLE')) {
            // Extracting the name of the old database and table from the
            // statement to make sure the parameters are correct.
            if (! empty($statement->name->database)) {
                $oldDatabase = $statement->name->database;
            }

            /**
             * Old table name.
             */
            $oldTable = $statement->name->table;

            // Finding the aliased database name.
            // The database might be empty so we have to add a few checks.
            $newDatabase = null;
            if (! empty($statement->name->database)) {
                $newDatabase = $statement->name->database;
                if (! empty($aliases[$oldDatabase]['alias'])) {
                    $newDatabase = $aliases[$oldDatabase]['alias'];
                }
            }

            // Finding the aliases table name.
            $newTable = $oldTable;
            if (! empty($aliases[$oldDatabase]['tables'][$oldTable]['alias'])) {
                $newTable = $aliases[$oldDatabase]['tables'][$oldTable]['alias'];
            }

            // Replacing new values.
            if (($statement->name->database !== $newDatabase) || ($statement->name->table !== $newTable)) {
                $statement->name->database = $newDatabase;
                $statement->name->table = $newTable;
                $statement->name->expr = ''; // Force rebuild.
                $flag = true;
            }

            /** @var CreateDefinition[] $fields */
            $fields = $statement->fields;
            foreach ($fields as $field) {
                // Column name.
                if (! empty($field->type)) {
                    if (! empty($aliases[$oldDatabase]['tables'][$oldTable]['columns'][$field->name])) {
                        $field->name = $aliases[$oldDatabase]['tables'][$oldTable]['columns'][$field->name];
                        $flag = true;
                    }
                }

                // Key's columns.
                if (! empty($field->key)) {
                    foreach ($field->key->columns as $key => $column) {
                        if (! isset($column['name'])) {
                            // In case the column has no name field
                            continue;
                        }

                        if (empty($aliases[$oldDatabase]['tables'][$oldTable]['columns'][$column['name']])) {
                            continue;
                        }

                        $columnAliases = $aliases[$oldDatabase]['tables'][$oldTable]['columns'];
                        $field->key->columns[$key]['name'] = $columnAliases[$column['name']];
                        $flag = true;
                    }
                }

                // References.
                if (empty($field->references)) {
                    continue;
                }

                $refTable = $field->references->table->table;
                // Replacing table.
                if (! empty($aliases[$oldDatabase]['tables'][$refTable]['alias'])) {
                    $field->references->table->table = $aliases[$oldDatabase]['tables'][$refTable]['alias'];
                    $field->references->table->expr = '';
                    $flag = true;
                }

                // Replacing column names.
                foreach ($field->references->columns as $key => $column) {
                    if (empty($aliases[$oldDatabase]['tables'][$refTable]['columns'][$column])) {
                        continue;
                    }

                    $field->references->columns[$key] = $aliases[$oldDatabase]['tables'][$refTable]['columns'][$column];
                    $flag = true;
                }
            }
        } elseif ($statement->options->has('TRIGGER')) {
            // Extracting the name of the old database and table from the
            // statement to make sure the parameters are correct.
            if (! empty($statement->table->database)) {
                $oldDatabase = $statement->table->database;
            }

            /**
             * Old table name.
             */
            $oldTable = $statement->table->table;

            if (! empty($aliases[$oldDatabase]['tables'][$oldTable]['alias'])) {
                $statement->table->table = $aliases[$oldDatabase]['tables'][$oldTable]['alias'];
                $statement->table->expr = ''; // Force rebuild.
                $flag = true;
            }
        }

        if (
            $statement->options->has('TRIGGER')
            || $statement->options->has('PROCEDURE')
            || $statement->options->has('FUNCTION')
            || $statement->options->has('VIEW')
        ) {
            // Replacing the body.
            for ($i = 0, $count = count((array) $statement->body); $i < $count; ++$i) {

                /**
                 * Token parsed at this moment.
                 *
                 * @var Token $token
                 */
                $token = $statement->body[$i];

                // Replacing only symbols (that are not variables) and unknown
                // identifiers.
                $isSymbol = $token->type === Token::TYPE_SYMBOL;
                $isKeyword = $token->type === Token::TYPE_KEYWORD;
                $isNone = $token->type === Token::TYPE_NONE;
                $replaceToken = $isSymbol
                    && (! ($token->flags & Token::FLAG_SYMBOL_VARIABLE))
                    || ($isKeyword
                    && (! ($token->flags & Token::FLAG_KEYWORD_RESERVED))
                    || $isNone);

                if (! $replaceToken) {
                    continue;
                }

                $alias = $this->getAlias($aliases, $token->value);
                if (empty($alias)) {
                    continue;
                }

                // Replacing the token.
                $token->token = Context::escape($alias);
                $flag = true;
            }
        }

        return $statement->build();
    }

    /**
     * Generate comment
     *
     * @param string      $crlf         Carriage return character
     * @param string|null $sqlStatement SQL statement
     * @param string      $comment1     Comment for dumped table
     * @param string      $comment2     Comment for current table
     * @param string      $tableAlias   Table alias
     * @param string      $compat       Compatibility mode
     *
     * @return string
     */
    protected function generateComment(
        $crlf,
        ?string $sqlStatement,
        $comment1,
        $comment2,
        $tableAlias,
        $compat
    ) {
        if (! isset($sqlStatement)) {
            if (isset($GLOBALS['no_constraints_comments'])) {
                $sqlStatement = '';
            } else {
                $sqlStatement = $crlf
                    . $this->exportComment()
                    . $this->exportComment($comment1)
                    . $this->exportComment();
            }
        }

        // comments for current table
        if (! isset($GLOBALS['no_constraints_comments'])) {
            $sqlStatement .= $crlf
                . $this->exportComment()
                . $this->exportComment(
                    $comment2 . ' ' . Util::backquoteCompat(
                        $tableAlias,
                        $compat,
                        isset($GLOBALS['sql_backquotes'])
                    )
                )
                . $this->exportComment();
        }

        return $sqlStatement;
    }
}
