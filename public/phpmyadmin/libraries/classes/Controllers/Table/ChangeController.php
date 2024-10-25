<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function array_fill;
use function count;
use function is_array;
use function str_contains;
use function strlen;
use function strpos;

/**
 * Displays form for editing and inserting new table rows.
 */
class ChangeController extends AbstractController
{
    /** @var InsertEdit */
    private $insertEdit;

    /** @var Relation */
    private $relation;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        InsertEdit $insertEdit,
        Relation $relation
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->insertEdit = $insertEdit;
        $this->relation = $relation;
    }

    public function __invoke(): void
    {
        global $cfg, $db, $table, $text_dir, $disp_message, $urlParams;
        global $errorUrl, $where_clause, $unsaved_values, $insert_mode, $where_clause_array, $where_clauses;
        global $result, $rows, $found_unique_key, $after_insert, $comments_map, $table_columns;
        global $chg_evt_handler, $timestamp_seen, $columns_cnt, $tabindex;
        global $tabindex_for_value, $o_rows, $biggest_max_file_size, $has_blob_field;
        global $jsvkey, $vkey, $current_result, $repopulate, $checked;

        $pageSettings = new PageSettings('Edit');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        DbTableExists::check();

        /**
         * Determine whether Insert or Edit and set global variables
         */
        [
            $insert_mode,
            $where_clause,
            $where_clause_array,
            $where_clauses,
            $result,
            $rows,
            $found_unique_key,
            $after_insert,
        ] = $this->insertEdit->determineInsertOrEdit($where_clause ?? null, $db, $table);
        // Increase number of rows if unsaved rows are more
        if (! empty($unsaved_values) && count($rows) < count($unsaved_values)) {
            $rows = array_fill(0, count($unsaved_values), false);
        }

        /**
         * Defines the url to return to in case of error in a sql statement
         * (at this point, $GLOBALS['goto'] will be set but could be empty)
         */
        if (empty($GLOBALS['goto'])) {
            if (strlen($table) > 0) {
                // avoid a problem (see bug #2202709)
                $GLOBALS['goto'] = Url::getFromRoute('/table/sql');
            } else {
                $GLOBALS['goto'] = Url::getFromRoute('/database/sql');
            }
        }

        $urlParams = [
            'db' => $db,
            'sql_query' => $_POST['sql_query'] ?? '',
        ];

        if (strpos($GLOBALS['goto'] ?? '', 'index.php?route=/table') === 0) {
            $urlParams['table'] = $table;
        }

        $errorUrl = $GLOBALS['goto'] . Url::getCommon(
            $urlParams,
            ! str_contains($GLOBALS['goto'], '?') ? '?' : '&'
        );
        unset($urlParams);

        $comments_map = $this->insertEdit->getCommentsMap($db, $table);

        /**
         * START REGULAR OUTPUT
         */

        $this->addScriptFiles([
            'makegrid.js',
            'sql.js',
            'table/change.js',
            'vendor/jquery/additional-methods.js',
            'gis_data_editor.js',
        ]);

        /**
         * Displays the query submitted and its result
         *
         * $disp_message come from /table/replace
         */
        if (! empty($disp_message)) {
            $this->response->addHTML(Generator::getMessage($disp_message, null));
        }

        $table_columns = $this->insertEdit->getTableColumns($db, $table);

        // retrieve keys into foreign fields, if any
        $foreigners = $this->relation->getForeigners($db, $table);

        // Retrieve form parameters for insert/edit form
        $_form_params = $this->insertEdit->getFormParametersForInsertForm(
            $db,
            $table,
            $where_clauses,
            $where_clause_array,
            $errorUrl
        );

        /**
         * Displays the form
         */
        // autocomplete feature of IE kills the "onchange" event handler and it
        //        must be replaced by the "onpropertychange" one in this case
        $chg_evt_handler = 'onchange';
        // Had to put the URI because when hosted on an https server,
        // some browsers send wrongly this form to the http server.

        $html_output = '';
        // Set if we passed the first timestamp field
        $timestamp_seen = false;
        $columns_cnt = count($table_columns);

        $tabindex = 0;
        $tabindex_for_value = 0;
        $o_rows = 0;
        $biggest_max_file_size = 0;

        $urlParams['db'] = $db;
        $urlParams['table'] = $table;
        $urlParams = $this->insertEdit->urlParamsInEditMode($urlParams, $where_clause_array);

        $has_blob_field = false;
        foreach ($table_columns as $column) {
            if ($this->insertEdit->isColumn($column, ['blob', 'tinyblob', 'mediumblob', 'longblob'])) {
                $has_blob_field = true;
                break;
            }
        }

        //Insert/Edit form
        //If table has blob fields we have to disable ajax.
        $isUpload = $GLOBALS['config']->get('enable_upload');
        $html_output .= $this->insertEdit->getHtmlForInsertEditFormHeader($has_blob_field, $isUpload);

        $html_output .= Url::getHiddenInputs($_form_params);

        // user can toggle the display of Function column and column types
        // (currently does not work for multi-edits)
        if (! $cfg['ShowFunctionFields'] || ! $cfg['ShowFieldTypesInDataEditView']) {
            $html_output .= __('Show');
        }

        if (! $cfg['ShowFunctionFields']) {
            $html_output .= $this->insertEdit->showTypeOrFunction('function', $urlParams, false);
        }

        if (! $cfg['ShowFieldTypesInDataEditView']) {
            $html_output .= $this->insertEdit->showTypeOrFunction('type', $urlParams, false);
        }

        $GLOBALS['plugin_scripts'] = [];
        foreach ($rows as $row_id => $current_row) {
            if (empty($current_row)) {
                $current_row = [];
            }

            $jsvkey = $row_id;
            $vkey = '[multi_edit][' . $jsvkey . ']';

            $current_result = (isset($result) && is_array($result) && isset($result[$row_id])
                ? $result[$row_id]
                : $result);
            $repopulate = [];
            $checked = true;
            if (isset($unsaved_values[$row_id])) {
                $repopulate = $unsaved_values[$row_id];
                $checked = false;
            }

            if ($insert_mode && $row_id > 0) {
                $html_output .= $this->insertEdit->getHtmlForIgnoreOption($row_id, $checked);
            }

            $html_output .= $this->insertEdit->getHtmlForInsertEditRow(
                $urlParams,
                $table_columns,
                $comments_map,
                $timestamp_seen,
                $current_result,
                $chg_evt_handler,
                $jsvkey,
                $vkey,
                $insert_mode,
                $current_row,
                $o_rows,
                $tabindex,
                $columns_cnt,
                $isUpload,
                $foreigners,
                $tabindex_for_value,
                $table,
                $db,
                $row_id,
                $biggest_max_file_size,
                $text_dir,
                $repopulate,
                $where_clause_array
            );
        }

        $this->addScriptFiles($GLOBALS['plugin_scripts']);

        unset($unsaved_values, $checked, $repopulate, $GLOBALS['plugin_scripts']);

        if (! isset($after_insert)) {
            $after_insert = 'back';
        }

        $isNumeric = InsertEdit::isWhereClauseNumeric($where_clause);
        $html_output .= $this->template->render('table/insert/actions_panel', [
            'where_clause' => $where_clause,
            'after_insert' => $after_insert,
            'found_unique_key' => $found_unique_key,
            'is_numeric' => $isNumeric,
        ]);

        if ($biggest_max_file_size > 0) {
            $html_output .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . $biggest_max_file_size . '">' . "\n";
        }

        $html_output .= '</form>';

        $html_output .= $this->insertEdit->getHtmlForGisEditor();
        // end Insert/Edit form

        if ($insert_mode) {
            //Continue insertion form
            $html_output .= $this->insertEdit->getContinueInsertionForm($table, $db, $where_clause_array, $errorUrl);
        }

        $this->response->addHTML($html_output);
    }
}
