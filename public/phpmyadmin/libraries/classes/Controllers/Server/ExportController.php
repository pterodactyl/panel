<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function array_merge;

final class ExportController extends AbstractController
{
    /** @var Options */
    private $export;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, Options $export, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
        $this->export = $export;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $db, $table, $sql_query, $num_tables, $unlim_num_rows;
        global $tmp_select, $select_item, $errorUrl;

        $errorUrl = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $pageSettings = new PageSettings('Export');
        $pageSettingsErrorHtml = $pageSettings->getErrorHTML();
        $pageSettingsHtml = $pageSettings->getHTML();

        $this->addScriptFiles(['export.js']);

        $select_item = $tmp_select ?? '';
        $databases = $this->export->getDatabasesForSelectOptions($select_item);

        if (! isset($sql_query)) {
            $sql_query = '';
        }

        if (! isset($num_tables)) {
            $num_tables = 0;
        }

        if (! isset($unlim_num_rows)) {
            $unlim_num_rows = 0;
        }

        $GLOBALS['single_table'] = $_POST['single_table'] ?? $_GET['single_table'] ?? $GLOBALS['single_table'] ?? null;

        $exportList = Plugins::getExport('server', isset($GLOBALS['single_table']));

        if (empty($exportList)) {
            $this->response->addHTML(Message::error(
                __('Could not load export plugins, please check your installation!')
            )->getDisplay());

            return;
        }

        $options = $this->export->getOptions(
            'server',
            $db,
            $table,
            $sql_query,
            $num_tables,
            $unlim_num_rows,
            $exportList
        );

        $this->render('server/export/index', array_merge($options, [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'databases' => $databases,
        ]));
    }
}
