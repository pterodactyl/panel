<?php
/**
 * Functions used for database and table tracking
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Html\Generator;

use function __;
use function array_key_exists;
use function array_merge;
use function array_multisort;
use function count;
use function date;
use function htmlspecialchars;
use function in_array;
use function ini_set;
use function is_array;
use function mb_strstr;
use function preg_replace;
use function rtrim;
use function sprintf;
use function strlen;
use function strtotime;

use const SORT_ASC;

/**
 * PhpMyAdmin\Tracking class
 */
class Tracking
{
    /** @var SqlQueryForm */
    private $sqlQueryForm;

    /** @var Template */
    public $template;

    /** @var Relation */
    protected $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        SqlQueryForm $sqlQueryForm,
        Template $template,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        $this->sqlQueryForm = $sqlQueryForm;
        $this->template = $template;
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    /**
     * Filters tracking entries
     *
     * @param array $data           the entries to filter
     * @param int   $filter_ts_from "from" date
     * @param int   $filter_ts_to   "to" date
     * @param array $filter_users   users
     *
     * @return array filtered entries
     */
    public function filter(
        array $data,
        $filter_ts_from,
        $filter_ts_to,
        array $filter_users
    ): array {
        $tmp_entries = [];
        $id = 0;
        foreach ($data as $entry) {
            $timestamp = strtotime($entry['date']);
            $filtered_user = in_array($entry['username'], $filter_users);
            if (
                $timestamp >= $filter_ts_from
                && $timestamp <= $filter_ts_to
                && (in_array('*', $filter_users) || $filtered_user)
            ) {
                $tmp_entries[] = [
                    'id' => $id,
                    'timestamp' => $timestamp,
                    'username' => $entry['username'],
                    'statement' => $entry['statement'],
                ];
            }

            $id++;
        }

        return $tmp_entries;
    }

    /**
     * Function to get the list versions of the table
     *
     * @return ResultInterface|false
     */
    public function getListOfVersionsOfTable(string $db, string $table)
    {
        $trackingFeature = $this->relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        $query = sprintf(
            'SELECT * FROM %s.%s WHERE db_name = \'%s\' AND table_name = \'%s\' ORDER BY version DESC',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $this->dbi->escapeString($db),
            $this->dbi->escapeString($table)
        );

        return $this->dbi->queryAsControlUser($query);
    }

    /**
     * Function to get html for main page parts that do not use $_REQUEST
     *
     * @param array  $urlParams   url parameters
     * @param string $textDir     text direction
     * @param int    $lastVersion last tracking version
     *
     * @return string
     */
    public function getHtmlForMainPage(
        string $db,
        string $table,
        $urlParams,
        $textDir,
        $lastVersion = null
    ) {
        global $cfg;

        $selectableTablesSqlResult = $this->getSqlResultForSelectableTables($db);
        $selectableTablesEntries = [];
        $selectableTablesNumRows = 0;
        if ($selectableTablesSqlResult !== false) {
            foreach ($selectableTablesSqlResult as $entry) {
                $entry['is_tracked'] = Tracker::isTracked($entry['db_name'], $entry['table_name']);
                $selectableTablesEntries[] = $entry;
            }

            $selectableTablesNumRows = $selectableTablesSqlResult->numRows();
        }

        $versionSqlResult = $this->getListOfVersionsOfTable($db, $table);
        if ($lastVersion === null && $versionSqlResult !== false) {
            $lastVersion = $this->getTableLastVersionNumber($versionSqlResult);
        }

        $versions = [];
        if ($versionSqlResult !== false) {
            $versions = $versionSqlResult->fetchAllAssoc();
        }

        $type = $this->dbi->getTable($db, $table)->isView() ? 'view' : 'table';

        return $this->template->render('table/tracking/main', [
            'url_params' => $urlParams,
            'db' => $db,
            'table' => $table,
            'selectable_tables_num_rows' => $selectableTablesNumRows,
            'selectable_tables_entries' => $selectableTablesEntries,
            'selected_table' => $_POST['table'] ?? null,
            'last_version' => $lastVersion,
            'versions' => $versions,
            'type' => $type,
            'default_statements' => $cfg['Server']['tracking_default_statements'],
            'text_dir' => $textDir,
        ]);
    }

    /**
     * Function to get the last version number of a table
     */
    public function getTableLastVersionNumber(ResultInterface $result): int
    {
        return (int) $result->fetchValue('version');
    }

    /**
     * Function to get sql results for selectable tables
     *
     * @return ResultInterface|false
     */
    public function getSqlResultForSelectableTables(string $db)
    {
        $trackingFeature = $this->relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        $sql_query = ' SELECT DISTINCT db_name, table_name FROM ' .
            Util::backquote($trackingFeature->database) . '.' .
            Util::backquote($trackingFeature->tracking) .
            " WHERE db_name = '" . $this->dbi->escapeString($db) .
            "' " .
            ' ORDER BY db_name, table_name';

        return $this->dbi->queryAsControlUser($sql_query);
    }

    /**
     * Function to get html for tracking report and tracking report export
     *
     * @param array $data             data
     * @param array $url_params       url params
     * @param bool  $selection_schema selection schema
     * @param bool  $selection_data   selection data
     * @param bool  $selection_both   selection both
     * @param int   $filter_ts_to     filter time stamp from
     * @param int   $filter_ts_from   filter time stamp tp
     * @param array $filter_users     filter users
     *
     * @return string
     */
    public function getHtmlForTrackingReport(
        array $data,
        array $url_params,
        $selection_schema,
        $selection_data,
        $selection_both,
        $filter_ts_to,
        $filter_ts_from,
        array $filter_users
    ) {
        $html = '<h3>' . __('Tracking report')
            . '  [<a href="' . Url::getFromRoute('/table/tracking', $url_params) . '">' . __('Close')
            . '</a>]</h3>';

        $html .= '<small>' . __('Tracking statements') . ' '
            . htmlspecialchars($data['tracking']) . '</small><br>';
        $html .= '<br>';

        [$str1, $str2, $str3, $str4, $str5] = $this->getHtmlForElementsOfTrackingReport(
            $selection_schema,
            $selection_data,
            $selection_both
        );

        // Prepare delete link content here
        $drop_image_or_text = '';
        if (Util::showIcons('ActionLinksMode')) {
            $drop_image_or_text .= Generator::getImage(
                'b_drop',
                __('Delete tracking data row from report')
            );
        }

        if (Util::showText('ActionLinksMode')) {
            $drop_image_or_text .= __('Delete');
        }

        /*
         *  First, list tracked data definition statements
         */
        if (count($data['ddlog']) == 0 && count($data['dmlog']) === 0) {
            $msg = Message::notice(__('No data'));
            echo $msg->getDisplay();
        }

        $html .= $this->getHtmlForTrackingReportExportForm1(
            $data,
            $url_params,
            $selection_schema,
            $selection_data,
            $selection_both,
            $filter_ts_to,
            $filter_ts_from,
            $filter_users,
            $str1,
            $str2,
            $str3,
            $str4,
            $str5,
            $drop_image_or_text
        );

        $html .= $this->getHtmlForTrackingReportExportForm2($url_params, $str1, $str2, $str3, $str4, $str5);

        $html .= "<br><br><hr><br>\n";

        return $html;
    }

    /**
     * Generate HTML element for report form
     *
     * @param bool $selection_schema selection schema
     * @param bool $selection_data   selection data
     * @param bool $selection_both   selection both
     *
     * @return array
     */
    public function getHtmlForElementsOfTrackingReport(
        $selection_schema,
        $selection_data,
        $selection_both
    ) {
        $str1 = '<select name="logtype">'
            . '<option value="schema"'
            . ($selection_schema ? ' selected="selected"' : '') . '>'
            . __('Structure only') . '</option>'
            . '<option value="data"'
            . ($selection_data ? ' selected="selected"' : '') . '>'
            . __('Data only') . '</option>'
            . '<option value="schema_and_data"'
            . ($selection_both ? ' selected="selected"' : '') . '>'
            . __('Structure and data') . '</option>'
            . '</select>';
        $str2 = '<input type="text" name="date_from" value="'
            . htmlspecialchars($_POST['date_from']) . '" size="19">';
        $str3 = '<input type="text" name="date_to" value="'
            . htmlspecialchars($_POST['date_to']) . '" size="19">';
        $str4 = '<input type="text" name="users" value="'
            . htmlspecialchars($_POST['users']) . '">';
        $str5 = '<input type="hidden" name="list_report" value="1">'
            . '<input class="btn btn-primary" type="submit" value="' . __('Go') . '">';

        return [
            $str1,
            $str2,
            $str3,
            $str4,
            $str5,
        ];
    }

    /**
     * Generate HTML for export form
     *
     * @param array  $data               data
     * @param array  $url_params         url params
     * @param bool   $selection_schema   selection schema
     * @param bool   $selection_data     selection data
     * @param bool   $selection_both     selection both
     * @param int    $filter_ts_to       filter time stamp from
     * @param int    $filter_ts_from     filter time stamp tp
     * @param array  $filter_users       filter users
     * @param string $str1               HTML for logtype select
     * @param string $str2               HTML for "from date"
     * @param string $str3               HTML for "to date"
     * @param string $str4               HTML for user
     * @param string $str5               HTML for "list report"
     * @param string $drop_image_or_text HTML for image or text
     *
     * @return string HTML for form
     */
    public function getHtmlForTrackingReportExportForm1(
        array $data,
        array $url_params,
        $selection_schema,
        $selection_data,
        $selection_both,
        $filter_ts_to,
        $filter_ts_from,
        array $filter_users,
        $str1,
        $str2,
        $str3,
        $str4,
        $str5,
        $drop_image_or_text
    ) {
        $ddlog_count = 0;

        $html = '<form method="post" action="' . Url::getFromRoute('/table/tracking') . '">';
        $html .= Url::getHiddenInputs($url_params + [
            'report' => 'true',
            'version' => $_POST['version'],
        ]);

        $html .= sprintf(
            __('Show %1$s with dates from %2$s to %3$s by user %4$s %5$s'),
            $str1,
            $str2,
            $str3,
            $str4,
            $str5
        );

        if ($selection_schema || $selection_both && count($data['ddlog']) > 0) {
            [$temp, $ddlog_count] = $this->getHtmlForDataDefinitionStatements(
                $data,
                $filter_users,
                $filter_ts_from,
                $filter_ts_to,
                $url_params,
                $drop_image_or_text
            );
            $html .= $temp;
            unset($temp);
        }

        /*
         *  Secondly, list tracked data manipulation statements
         */
        if (($selection_data || $selection_both) && count($data['dmlog']) > 0) {
            $html .= $this->getHtmlForDataManipulationStatements(
                $data,
                $filter_users,
                $filter_ts_from,
                $filter_ts_to,
                $url_params,
                $ddlog_count,
                $drop_image_or_text
            );
        }

        $html .= '</form>';

        return $html;
    }

    /**
     * Generate HTML for export form
     *
     * @param array  $url_params Parameters
     * @param string $str1       HTML for logtype select
     * @param string $str2       HTML for "from date"
     * @param string $str3       HTML for "to date"
     * @param string $str4       HTML for user
     * @param string $str5       HTML for "list report"
     *
     * @return string HTML for form
     */
    public function getHtmlForTrackingReportExportForm2(
        array $url_params,
        $str1,
        $str2,
        $str3,
        $str4,
        $str5
    ) {
        $html = '<form method="post" action="' . Url::getFromRoute('/table/tracking') . '">';
        $html .= Url::getHiddenInputs($url_params + [
            'report' => 'true',
            'version' => $_POST['version'],
        ]);

        $html .= sprintf(
            __('Show %1$s with dates from %2$s to %3$s by user %4$s %5$s'),
            $str1,
            $str2,
            $str3,
            $str4,
            $str5
        );
        $html .= '</form>';

        $html .= '<form class="disableAjax" method="post" action="' . Url::getFromRoute('/table/tracking') . '">';
        $html .= Url::getHiddenInputs($url_params + [
            'report' => 'true',
            'version' => $_POST['version'],
            'logtype' => $_POST['logtype'],
            'date_from' => $_POST['date_from'],
            'date_to' => $_POST['date_to'],
            'users' => $_POST['users'],
            'report_export' => 'true',
        ]);

        $str_export1 = '<select name="export_type">'
            . '<option value="sqldumpfile">' . __('SQL dump (file download)')
            . '</option>'
            . '<option value="sqldump">' . __('SQL dump') . '</option>'
            . '<option value="execution" onclick="alert(\''
            . Sanitize::escapeJsString(
                __('This option will replace your table and contained data.')
            )
            . '\')">' . __('SQL execution') . '</option></select>';

        $str_export2 = '<input class="btn btn-primary" type="submit" value="' . __('Go') . '">';

        $html .= '<br>' . sprintf(__('Export as %s'), $str_export1)
            . $str_export2 . '<br>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Function to get html for data manipulation statements
     *
     * @param array  $data               data
     * @param array  $filter_users       filter users
     * @param int    $filter_ts_from     filter time staml from
     * @param int    $filter_ts_to       filter time stamp to
     * @param array  $url_params         url parameters
     * @param int    $ddlog_count        data definition log count
     * @param string $drop_image_or_text drop image or text
     *
     * @return string
     */
    public function getHtmlForDataManipulationStatements(
        array $data,
        array $filter_users,
        $filter_ts_from,
        $filter_ts_to,
        array $url_params,
        $ddlog_count,
        $drop_image_or_text
    ) {
        // no need for the second returned parameter
        [$html] = $this->getHtmlForDataStatements(
            $data,
            $filter_users,
            $filter_ts_from,
            $filter_ts_to,
            $url_params,
            $drop_image_or_text,
            'dmlog',
            __('Data manipulation statement'),
            $ddlog_count,
            'dml_versions'
        );

        return $html;
    }

    /**
     * Function to get html for data definition statements in schema snapshot
     *
     * @param array  $data               data
     * @param array  $filter_users       filter users
     * @param int    $filter_ts_from     filter time stamp from
     * @param int    $filter_ts_to       filter time stamp to
     * @param array  $url_params         url parameters
     * @param string $drop_image_or_text drop image or text
     *
     * @return array
     */
    public function getHtmlForDataDefinitionStatements(
        array $data,
        array $filter_users,
        $filter_ts_from,
        $filter_ts_to,
        array $url_params,
        $drop_image_or_text
    ) {
        [$html, $line_number] = $this->getHtmlForDataStatements(
            $data,
            $filter_users,
            $filter_ts_from,
            $filter_ts_to,
            $url_params,
            $drop_image_or_text,
            'ddlog',
            __('Data definition statement'),
            1,
            'ddl_versions'
        );

        return [
            $html,
            $line_number,
        ];
    }

    /**
     * Function to get html for data statements in schema snapshot
     *
     * @param array  $data            data
     * @param array  $filterUsers     filter users
     * @param int    $filterTsFrom    filter time stamp from
     * @param int    $filterTsTo      filter time stamp to
     * @param array  $urlParams       url parameters
     * @param string $dropImageOrText drop image or text
     * @param string $whichLog        dmlog|ddlog
     * @param string $headerMessage   message for this section
     * @param int    $lineNumber      line number
     * @param string $tableId         id for the table element
     *
     * @return array [$html, $lineNumber]
     */
    private function getHtmlForDataStatements(
        array $data,
        array $filterUsers,
        $filterTsFrom,
        $filterTsTo,
        array $urlParams,
        $dropImageOrText,
        $whichLog,
        $headerMessage,
        $lineNumber,
        $tableId
    ) {
        $offset = $lineNumber;
        $entries = [];
        foreach ($data[$whichLog] as $entry) {
            $timestamp = strtotime($entry['date']);
            if (
                $timestamp >= $filterTsFrom
                && $timestamp <= $filterTsTo
                && (in_array('*', $filterUsers)
                || in_array($entry['username'], $filterUsers))
            ) {
                $entry['formated_statement'] = Generator::formatSql($entry['statement'], true);
                $deleteParam = 'delete_' . $whichLog;
                $entry['url_params'] = Url::getCommon($urlParams + [
                    'report' => 'true',
                    'version' => $_POST['version'],
                    $deleteParam => $lineNumber - $offset,
                ], '');
                $entry['line_number'] = $lineNumber;
                $entries[] = $entry;
            }

            $lineNumber++;
        }

        $html = $this->template->render('table/tracking/report_table', [
            'table_id' => $tableId,
            'header_message' => $headerMessage,
            'entries' => $entries,
            'drop_image_or_text' => $dropImageOrText,
        ]);

        return [
            $html,
            $lineNumber,
        ];
    }

    /**
     * Function to get html for schema snapshot
     *
     * @param array $params url parameters
     */
    public function getHtmlForSchemaSnapshot(array $params): string
    {
        $html = '<h3>' . __('Structure snapshot')
            . '  [<a href="' . Url::getFromRoute('/table/tracking', $params) . '">' . __('Close')
            . '</a>]</h3>';
        $data = Tracker::getTrackedData($_POST['db'], $_POST['table'], $_POST['version']);

        // Get first DROP TABLE/VIEW and CREATE TABLE/VIEW statements
        $drop_create_statements = $data['ddlog'][0]['statement'];

        if (
            mb_strstr($data['ddlog'][0]['statement'], 'DROP TABLE')
            || mb_strstr($data['ddlog'][0]['statement'], 'DROP VIEW')
        ) {
            $drop_create_statements .= $data['ddlog'][1]['statement'];
        }

        // Print SQL code
        $html .= Generator::getMessage(
            sprintf(
                __('Version %s snapshot (SQL code)'),
                htmlspecialchars($_POST['version'])
            ),
            $drop_create_statements
        );

        // Unserialize snapshot
        $temp = Core::safeUnserialize($data['schema_snapshot']);
        if ($temp === null) {
            $temp = [
                'COLUMNS' => [],
                'INDEXES' => [],
            ];
        }

        $columns = $temp['COLUMNS'];
        $indexes = $temp['INDEXES'];
        $html .= $this->getHtmlForColumns($columns);

        if (count($indexes) > 0) {
            $html .= $this->getHtmlForIndexes($indexes);
        }

        $html .= '<br><hr><br>';

        return $html;
    }

    /**
     * Function to get html for displaying columns in the schema snapshot
     *
     * @param array $columns columns
     *
     * @return string
     */
    public function getHtmlForColumns(array $columns)
    {
        return $this->template->render('table/tracking/structure_snapshot_columns', ['columns' => $columns]);
    }

    /**
     * Function to get html for the indexes in schema snapshot
     *
     * @param array $indexes indexes
     *
     * @return string
     */
    public function getHtmlForIndexes(array $indexes)
    {
        return $this->template->render('table/tracking/structure_snapshot_indexes', ['indexes' => $indexes]);
    }

    /**
     * Function to handle the tracking report
     *
     * @param array $data tracked data
     *
     * @return string HTML for the message
     */
    public function deleteTrackingReportRows(string $db, string $table, array &$data)
    {
        $html = '';
        if (isset($_POST['delete_ddlog'])) {
            // Delete ddlog row data
            $html .= $this->deleteFromTrackingReportLog(
                $db,
                $table,
                $data,
                'ddlog',
                'DDL',
                __('Tracking data definition successfully deleted')
            );
        }

        if (isset($_POST['delete_dmlog'])) {
            // Delete dmlog row data
            $html .= $this->deleteFromTrackingReportLog(
                $db,
                $table,
                $data,
                'dmlog',
                'DML',
                __('Tracking data manipulation successfully deleted')
            );
        }

        return $html;
    }

    /**
     * Function to delete from a tracking report log
     *
     * @param array  $data      tracked data
     * @param string $which_log ddlog|dmlog
     * @param string $type      DDL|DML
     * @param string $message   success message
     *
     * @return string HTML for the message
     */
    public function deleteFromTrackingReportLog(string $db, string $table, array &$data, $which_log, $type, $message)
    {
        $html = '';
        $delete_id = $_POST['delete_' . $which_log];

        // Only in case of valid id
        if ($delete_id == (int) $delete_id) {
            unset($data[$which_log][$delete_id]);

            $successfullyDeleted = Tracker::changeTrackingData(
                $db,
                $table,
                $_POST['version'],
                $type,
                $data[$which_log]
            );
            if ($successfullyDeleted) {
                $msg = Message::success($message);
            } else {
                $msg = Message::rawError(__('Query error'));
            }

            $html .= $msg->getDisplay();
        }

        return $html;
    }

    /**
     * Function to export as sql dump
     *
     * @param array $entries entries
     *
     * @return string HTML SQL query form
     */
    public function exportAsSqlDump(string $db, string $table, array $entries)
    {
        $html = '';
        $new_query = '# '
            . __(
                'You can execute the dump by creating and using a temporary database. '
                . 'Please ensure that you have the privileges to do so.'
            )
            . "\n"
            . '# ' . __('Comment out these two lines if you do not need them.') . "\n"
            . "\n"
            . "CREATE database IF NOT EXISTS pma_temp_db; \n"
            . "USE pma_temp_db; \n"
            . "\n";

        foreach ($entries as $entry) {
            $new_query .= $entry['statement'];
        }

        $msg = Message::success(
            __('SQL statements exported. Please copy the dump or execute it.')
        );
        $html .= $msg->getDisplay();

        $html .= $this->sqlQueryForm->getHtml('', '', $new_query, 'sql');

        return $html;
    }

    /**
     * Function to export as sql execution
     *
     * @param array $entries entries
     */
    public function exportAsSqlExecution(array $entries): void
    {
        foreach ($entries as $entry) {
            $this->dbi->query("/*NOTRACK*/\n" . $entry['statement']);
        }
    }

    /**
     * Function to export as entries
     *
     * @param array $entries entries
     */
    public function exportAsFileDownload(array $entries): void
    {
        ini_set('url_rewriter.tags', '');

        // Replace all multiple whitespaces by a single space
        $table = htmlspecialchars(preg_replace('/\s+/', ' ', $_POST['table']));
        $dump = '# ' . sprintf(
            __('Tracking report for table `%s`'),
            $table
        )
        . "\n" . '# ' . date('Y-m-d H:i:s') . "\n";
        foreach ($entries as $entry) {
            $dump .= $entry['statement'];
        }

        $filename = 'log_' . $table . '.sql';
        ResponseRenderer::getInstance()->disable();
        Core::downloadHeader(
            $filename,
            'text/x-sql',
            strlen($dump)
        );
        echo $dump;

        exit;
    }

    /**
     * Function to activate or deactivate tracking
     *
     * @param string $action activate|deactivate
     *
     * @return string HTML for the success message
     */
    public function changeTracking(string $db, string $table, $action)
    {
        $html = '';
        if ($action === 'activate') {
            $status = Tracker::activateTracking($db, $table, $_POST['version']);
            $message = __('Tracking for %1$s was activated at version %2$s.');
        } else {
            $status = Tracker::deactivateTracking($db, $table, $_POST['version']);
            $message = __('Tracking for %1$s was deactivated at version %2$s.');
        }

        if ($status) {
            $msg = Message::success(
                sprintf(
                    $message,
                    htmlspecialchars($db . '.' . $table),
                    htmlspecialchars($_POST['version'])
                )
            );
            $html .= $msg->getDisplay();
        }

        return $html;
    }

    /**
     * Function to get tracking set
     *
     * @return string
     */
    public function getTrackingSet()
    {
        $tracking_set = '';

        // a key is absent from the request if it has been removed from
        // tracking_default_statements in the config
        if (isset($_POST['alter_table']) && $_POST['alter_table'] == true) {
            $tracking_set .= 'ALTER TABLE,';
        }

        if (isset($_POST['rename_table']) && $_POST['rename_table'] == true) {
            $tracking_set .= 'RENAME TABLE,';
        }

        if (isset($_POST['create_table']) && $_POST['create_table'] == true) {
            $tracking_set .= 'CREATE TABLE,';
        }

        if (isset($_POST['drop_table']) && $_POST['drop_table'] == true) {
            $tracking_set .= 'DROP TABLE,';
        }

        if (isset($_POST['alter_view']) && $_POST['alter_view'] == true) {
            $tracking_set .= 'ALTER VIEW,';
        }

        if (isset($_POST['create_view']) && $_POST['create_view'] == true) {
            $tracking_set .= 'CREATE VIEW,';
        }

        if (isset($_POST['drop_view']) && $_POST['drop_view'] == true) {
            $tracking_set .= 'DROP VIEW,';
        }

        if (isset($_POST['create_index']) && $_POST['create_index'] == true) {
            $tracking_set .= 'CREATE INDEX,';
        }

        if (isset($_POST['drop_index']) && $_POST['drop_index'] == true) {
            $tracking_set .= 'DROP INDEX,';
        }

        if (isset($_POST['insert']) && $_POST['insert'] == true) {
            $tracking_set .= 'INSERT,';
        }

        if (isset($_POST['update']) && $_POST['update'] == true) {
            $tracking_set .= 'UPDATE,';
        }

        if (isset($_POST['delete']) && $_POST['delete'] == true) {
            $tracking_set .= 'DELETE,';
        }

        if (isset($_POST['truncate']) && $_POST['truncate'] == true) {
            $tracking_set .= 'TRUNCATE,';
        }

        $tracking_set = rtrim($tracking_set, ',');

        return $tracking_set;
    }

    /**
     * Deletes a tracking version
     *
     * @param string $version tracking version
     *
     * @return string HTML of the success message
     */
    public function deleteTrackingVersion(string $db, string $table, $version)
    {
        $html = '';
        $versionDeleted = Tracker::deleteTracking($db, $table, $version);
        if ($versionDeleted) {
            $msg = Message::success(
                sprintf(
                    __('Version %1$s of %2$s was deleted.'),
                    htmlspecialchars($version),
                    htmlspecialchars($db . '.' . $table)
                )
            );
            $html .= $msg->getDisplay();
        }

        return $html;
    }

    /**
     * Function to create the tracking version
     *
     * @return string HTML of the success message
     */
    public function createTrackingVersion(string $db, string $table)
    {
        $html = '';
        $tracking_set = $this->getTrackingSet();

        $versionCreated = Tracker::createVersion(
            $db,
            $table,
            $_POST['version'],
            $tracking_set,
            $this->dbi->getTable($db, $table)->isView()
        );
        if ($versionCreated) {
            $msg = Message::success(
                sprintf(
                    __('Version %1$s was created, tracking for %2$s is active.'),
                    htmlspecialchars($_POST['version']),
                    htmlspecialchars($db . '.' . $table)
                )
            );
            $html .= $msg->getDisplay();
        }

        return $html;
    }

    /**
     * Create tracking version for multiple tables
     *
     * @param array $selected list of selected tables
     */
    public function createTrackingForMultipleTables(string $db, array $selected): void
    {
        $tracking_set = $this->getTrackingSet();

        foreach ($selected as $selected_table) {
            Tracker::createVersion(
                $db,
                $selected_table,
                $_POST['version'],
                $tracking_set,
                $this->dbi->getTable($db, $selected_table)->isView()
            );
        }
    }

    /**
     * Function to get the entries
     *
     * @param array $data           data
     * @param int   $filter_ts_from filter time stamp from
     * @param int   $filter_ts_to   filter time stamp to
     * @param array $filter_users   filter users
     *
     * @return array
     */
    public function getEntries(array $data, $filter_ts_from, $filter_ts_to, array $filter_users)
    {
        $entries = [];
        // Filtering data definition statements
        if ($_POST['logtype'] === 'schema' || $_POST['logtype'] === 'schema_and_data') {
            $entries = array_merge(
                $entries,
                $this->filter(
                    $data['ddlog'],
                    $filter_ts_from,
                    $filter_ts_to,
                    $filter_users
                )
            );
        }

        // Filtering data manipulation statements
        if ($_POST['logtype'] === 'data' || $_POST['logtype'] === 'schema_and_data') {
            $entries = array_merge(
                $entries,
                $this->filter(
                    $data['dmlog'],
                    $filter_ts_from,
                    $filter_ts_to,
                    $filter_users
                )
            );
        }

        // Sort it
        $ids = $timestamps = $usernames = $statements = [];
        foreach ($entries as $key => $row) {
            $ids[$key] = $row['id'];
            $timestamps[$key] = $row['timestamp'];
            $usernames[$key] = $row['username'];
            $statements[$key] = $row['statement'];
        }

        array_multisort($timestamps, SORT_ASC, $ids, SORT_ASC, $usernames, SORT_ASC, $statements, SORT_ASC, $entries);

        return $entries;
    }

    /**
     * Get HTML for tracked and untracked tables
     *
     * @param string $db        current database
     * @param array  $urlParams url parameters
     * @param string $textDir   text direction
     *
     * @return string HTML
     */
    public function getHtmlForDbTrackingTables(
        string $db,
        array $urlParams,
        string $textDir
    ) {
        $trackingFeature = $this->relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return '';
        }

        // Prepare statement to get HEAD version
        $allTablesQuery = ' SELECT table_name, MAX(version) as version FROM ' .
            Util::backquote($trackingFeature->database) . '.' .
            Util::backquote($trackingFeature->tracking) .
            ' WHERE db_name = \'' . $this->dbi->escapeString($db) .
            '\' ' .
            ' GROUP BY table_name' .
            ' ORDER BY table_name ASC';

        $allTablesResult = $this->dbi->queryAsControlUser($allTablesQuery);
        $untrackedTables = $this->getUntrackedTables($db);

        // If a HEAD version exists
        $versions = [];
        while ($oneResult = $allTablesResult->fetchRow()) {
            [$tableName, $versionNumber] = $oneResult;
            $tableQuery = ' SELECT * FROM ' .
                Util::backquote($trackingFeature->database) . '.' .
                Util::backquote($trackingFeature->tracking) .
                ' WHERE `db_name` = \''
                . $this->dbi->escapeString($db)
                . '\' AND `table_name`  = \''
                . $this->dbi->escapeString($tableName)
                . '\' AND `version` = \'' . $versionNumber . '\'';

            $versions[] = $this->dbi->queryAsControlUser($tableQuery)->fetchAssoc();
        }

        return $this->template->render('database/tracking/tables', [
            'db' => $db,
            'head_version_exists' => $versions !== [],
            'untracked_tables_exists' => count($untrackedTables) > 0,
            'versions' => $versions,
            'url_params' => $urlParams,
            'text_dir' => $textDir,
            'untracked_tables' => $untrackedTables,
        ]);
    }

    /**
     * Helper function: Recursive function for getting table names from $table_list
     *
     * @param array  $table_list Table list
     * @param string $db         Current database
     * @param bool   $testing    Testing
     *
     * @return array
     */
    public function extractTableNames(array $table_list, $db, $testing = false)
    {
        global $cfg;

        $untracked_tables = [];
        $sep = $cfg['NavigationTreeTableSeparator'];

        foreach ($table_list as $value) {
            if (is_array($value) && array_key_exists('is' . $sep . 'group', $value) && $value['is' . $sep . 'group']) {
                // Recursion step
                $untracked_tables = array_merge($this->extractTableNames($value, $db, $testing), $untracked_tables);
            } elseif (is_array($value) && ($testing || Tracker::getVersion($db, $value['Name']) == -1)) {
                $untracked_tables[] = $value['Name'];
            }
        }

        return $untracked_tables;
    }

    /**
     * Get untracked tables
     *
     * @param string $db current database
     *
     * @return array
     */
    public function getUntrackedTables($db)
    {
        $table_list = Util::getTableList($db);

        //Use helper function to get table list recursively.
        return $this->extractTableNames($table_list, $db);
    }
}
