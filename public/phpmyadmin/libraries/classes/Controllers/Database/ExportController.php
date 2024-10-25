<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Export;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function is_array;

final class ExportController extends AbstractController
{
    /** @var Export */
    private $export;

    /** @var Options */
    private $exportOptions;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        Export $export,
        Options $exportOptions
    ) {
        parent::__construct($response, $template, $db);
        $this->export = $export;
        $this->exportOptions = $exportOptions;
    }

    public function __invoke(): void
    {
        global $db, $table, $sub_part, $urlParams, $sql_query;
        global $tables, $num_tables, $total_num_tables, $tooltip_truename;
        global $tooltip_aliasname, $pos, $table_select, $unlim_num_rows, $cfg, $errorUrl;

        $pageSettings = new PageSettings('Export');
        $pageSettingsErrorHtml = $pageSettings->getErrorHTML();
        $pageSettingsHtml = $pageSettings->getHTML();

        $this->addScriptFiles(['export.js']);

        // $sub_part is used in Util::getDbInfo() to see if we are coming from
        // /database/export, in which case we don't obey $cfg['MaxTableList']
        $sub_part = '_export';

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $urlParams['goto'] = Url::getFromRoute('/database/export');

        [
            $tables,
            $num_tables,
            $total_num_tables,
            $sub_part,,,
            $tooltip_truename,
            $tooltip_aliasname,
            $pos,
        ] = Util::getDbInfo($db, $sub_part);

        // exit if no tables in db found
        if ($num_tables < 1) {
            $this->response->addHTML(
                Message::error(__('No tables found in database.'))->getDisplay()
            );

            return;
        }

        if (! empty($_POST['selected_tbl']) && empty($table_select)) {
            $table_select = $_POST['selected_tbl'];
        }

        $tablesForMultiValues = [];

        foreach ($tables as $each_table) {
            if (isset($_POST['table_select']) && is_array($_POST['table_select'])) {
                $is_checked = $this->export->getCheckedClause($each_table['Name'], $_POST['table_select']);
            } elseif (isset($table_select)) {
                $is_checked = $this->export->getCheckedClause($each_table['Name'], $table_select);
            } else {
                $is_checked = true;
            }

            if (isset($_POST['table_structure']) && is_array($_POST['table_structure'])) {
                $structure_checked = $this->export->getCheckedClause($each_table['Name'], $_POST['table_structure']);
            } else {
                $structure_checked = $is_checked;
            }

            if (isset($_POST['table_data']) && is_array($_POST['table_data'])) {
                $data_checked = $this->export->getCheckedClause($each_table['Name'], $_POST['table_data']);
            } else {
                $data_checked = $is_checked;
            }

            $tablesForMultiValues[] = [
                'name' => $each_table['Name'],
                'is_checked_select' => $is_checked,
                'is_checked_structure' => $structure_checked,
                'is_checked_data' => $data_checked,
            ];
        }

        if (! isset($sql_query)) {
            $sql_query = '';
        }

        if (! isset($unlim_num_rows)) {
            $unlim_num_rows = 0;
        }

        $isReturnBackFromRawExport = isset($_POST['export_type']) && $_POST['export_type'] === 'raw';
        if (isset($_POST['raw_query']) || $isReturnBackFromRawExport) {
            $export_type = 'raw';
        } else {
            $export_type = 'database';
        }

        $GLOBALS['single_table'] = $_POST['single_table'] ?? $_GET['single_table'] ?? $GLOBALS['single_table'] ?? null;

        $exportList = Plugins::getExport($export_type, isset($GLOBALS['single_table']));

        if (empty($exportList)) {
            $this->response->addHTML(Message::error(
                __('Could not load export plugins, please check your installation!')
            )->getDisplay());

            return;
        }

        $options = $this->exportOptions->getOptions(
            $export_type,
            $db,
            $table,
            $sql_query,
            $num_tables,
            $unlim_num_rows,
            $exportList
        );

        $this->render('database/export/index', array_merge($options, [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'structure_or_data_forced' => $_POST['structure_or_data_forced'] ?? 0,
            'tables' => $tablesForMultiValues,
        ]));
    }
}
