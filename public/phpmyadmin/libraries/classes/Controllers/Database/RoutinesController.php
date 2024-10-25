<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function in_array;
use function strlen;

/**
 * Routines management.
 */
class RoutinesController extends AbstractController
{
    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        CheckUserPrivileges $checkUserPrivileges,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db);
        $this->checkUserPrivileges = $checkUserPrivileges;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $db, $table, $tables, $num_tables, $total_num_tables, $sub_part;
        global $tooltip_truename, $tooltip_aliasname, $pos;
        global $errors, $errorUrl, $urlParams, $cfg;

        $this->addScriptFiles(['database/routines.js']);

        $type = $_REQUEST['type'] ?? null;

        $this->checkUserPrivileges->getPrivileges();

        if (! $this->response->isAjax()) {
            /**
             * Displays the header and tabs
             */
            if (! empty($table) && in_array($table, $this->dbi->getTables($db))) {
                Util::checkParameters(['db', 'table']);

                $urlParams = ['db' => $db, 'table' => $table];
                $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
                $errorUrl .= Url::getCommon($urlParams, '&');

                DbTableExists::check();
            } else {
                $table = '';

                Util::checkParameters(['db']);

                $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
                $errorUrl .= Url::getCommon(['db' => $db], '&');

                if (! $this->hasDatabase()) {
                    return;
                }

                [
                    $tables,
                    $num_tables,
                    $total_num_tables,
                    $sub_part,,,
                    $tooltip_truename,
                    $tooltip_aliasname,
                    $pos,
                ] = Util::getDbInfo($db, $sub_part ?? '');
            }
        } elseif (strlen($db) > 0) {
            $this->dbi->selectDb($db);
        }

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $errors = [];

        $routines = new Routines($this->dbi, $this->template, $this->response);

        $routines->handleEditor();
        $routines->handleExecute();
        $routines->export();

        if (! isset($type) || ! in_array($type, ['FUNCTION', 'PROCEDURE'])) {
            $type = null;
        }

        $items = $this->dbi->getRoutines($db, $type);
        $isAjax = $this->response->isAjax() && empty($_REQUEST['ajax_page_request']);

        $rows = '';
        foreach ($items as $item) {
            $rows .= $routines->getRow($item, $isAjax ? 'ajaxInsert hide' : '');
        }

        $this->render('database/routines/index', [
            'db' => $db,
            'table' => $table,
            'items' => $items,
            'rows' => $rows,
            'has_privilege' => Util::currentUserHasPrivilege('CREATE ROUTINE', $db, $table),
        ]);
    }
}
