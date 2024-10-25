<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function mb_strpos;
use function str_contains;
use function strlen;
use function urlencode;

class SqlController extends AbstractController
{
    /** @var Sql */
    private $sql;

    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Sql $sql,
        CheckUserPrivileges $checkUserPrivileges,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->sql = $sql;
        $this->checkUserPrivileges = $checkUserPrivileges;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $cfg, $db, $display_query, $sql_query, $table;
        global $ajax_reload, $goto, $errorUrl, $find_real_end, $unlim_num_rows, $import_text, $disp_query;
        global $extra_data, $message_to_show, $sql_data, $disp_message, $complete_query;
        global $is_gotofile, $back, $table_from_sql;

        $this->checkUserPrivileges->getPrivileges();

        $pageSettings = new PageSettings('Browse');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $this->addScriptFiles([
            'vendor/jquery/jquery.uitablefilter.js',
            'table/change.js',
            'indexes.js',
            'gis_data_editor.js',
            'multi_column_sort.js',
        ]);

        /**
         * Set ajax_reload in the response if it was already set
         */
        if (isset($ajax_reload) && $ajax_reload['reload'] === true) {
            $this->response->addJSON('ajax_reload', $ajax_reload);
        }

        /**
         * Defines the url to return to in case of error in a sql statement
         */
        $is_gotofile = true;
        if (empty($goto)) {
            if (empty($table)) {
                $goto = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
            } else {
                $goto = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            }
        }

        if (! isset($errorUrl)) {
            $errorUrl = ! empty($back) ? $back : $goto;
            $errorUrl .= Url::getCommon(
                ['db' => $GLOBALS['db']],
                ! str_contains($errorUrl, '?') ? '?' : '&'
            );
            if (
                (mb_strpos(' ' . $errorUrl, 'db_') !== 1 || ! str_contains($errorUrl, '?route=/database/'))
                && strlen($table) > 0
            ) {
                $errorUrl .= '&amp;table=' . urlencode($table);
            }
        }

        // Coming from a bookmark dialog
        if (isset($_POST['bkm_fields']['bkm_sql_query'])) {
            $sql_query = $_POST['bkm_fields']['bkm_sql_query'];
        } elseif (isset($_POST['sql_query'])) {
            $sql_query = $_POST['sql_query'];
        } elseif (isset($_GET['sql_query'], $_GET['sql_signature'])) {
            if (Core::checkSqlQuerySignature($_GET['sql_query'], $_GET['sql_signature'])) {
                $sql_query = $_GET['sql_query'];
            }
        }

        // This one is just to fill $db
        if (isset($_POST['bkm_fields']['bkm_database'])) {
            $db = $_POST['bkm_fields']['bkm_database'];
        }

        // Default to browse if no query set and we have table
        // (needed for browsing from DefaultTabTable)
        if (empty($sql_query) && strlen($table) > 0 && strlen($db) > 0) {
            $sql_query = $this->sql->getDefaultSqlQueryForBrowse($db, $table);

            // set $goto to what will be displayed if query returns 0 rows
            $goto = '';
        } else {
            // Now we can check the parameters
            Util::checkParameters(['sql_query']);
        }

        /**
         * Parse and analyze the query
         */
        [
            $analyzed_sql_results,
            $db,
            $table_from_sql,
        ] = ParseAnalyze::sqlQuery($sql_query, $db);

        if ($table != $table_from_sql && ! empty($table_from_sql)) {
            $table = $table_from_sql;
        }

        /**
         * Check rights in case of DROP DATABASE
         *
         * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
         * but since a malicious user may pass this variable by url/form, we don't take
         * into account this case.
         */
        if (
            $this->sql->hasNoRightsToDropDatabase(
                $analyzed_sql_results,
                $cfg['AllowUserDropDatabase'],
                $this->dbi->isSuperUser()
            )
        ) {
            Generator::mysqlDie(
                __('"DROP DATABASE" statements are disabled.'),
                '',
                false,
                $errorUrl
            );
        }

        /**
         * Need to find the real end of rows?
         */
        if (isset($find_real_end) && $find_real_end) {
            $unlim_num_rows = $this->sql->findRealEndOfRows($db, $table);
        }

        /**
         * Bookmark add
         */
        if (isset($_POST['store_bkm'])) {
            $this->addBookmark($goto);

            return;
        }

        /**
         * Sets or modifies the $goto variable if required
         */
        if ($goto === Url::getFromRoute('/sql')) {
            $is_gotofile = false;
            $goto = Url::getFromRoute('/sql', [
                'db' => $db,
                'table' => $table,
                'sql_query' => $sql_query,
            ]);
        }

        $this->response->addHTML($this->sql->executeQueryAndSendQueryResponse(
            $analyzed_sql_results,
            $is_gotofile,
            $db,
            $table,
            $find_real_end ?? null,
            $import_text ?? null,
            $extra_data ?? null,
            $message_to_show ?? null,
            $sql_data ?? null,
            $goto,
            isset($disp_query) ? $display_query : null,
            $disp_message ?? null,
            $sql_query,
            $complete_query ?? null
        ));
    }

    private function addBookmark(string $goto): void
    {
        $bookmark = Bookmark::createBookmark(
            $this->dbi,
            $_POST['bkm_fields'],
            isset($_POST['bkm_all_users']) && $_POST['bkm_all_users'] === 'true'
        );

        $result = null;
        if ($bookmark instanceof Bookmark) {
            $result = $bookmark->save();
        }

        if (! $this->response->isAjax()) {
            Core::sendHeaderLocation('./' . $goto . '&label=' . $_POST['bkm_fields']['bkm_label']);

            return;
        }

        if ($result) {
            $msg = Message::success(__('Bookmark %s has been created.'));
            $msg->addParam($_POST['bkm_fields']['bkm_label']);
            $this->response->addJSON('message', $msg);

            return;
        }

        $msg = Message::error(__('Bookmark not created!'));
        $this->response->setRequestStatus(false);
        $this->response->addJSON('message', $msg);
    }
}
