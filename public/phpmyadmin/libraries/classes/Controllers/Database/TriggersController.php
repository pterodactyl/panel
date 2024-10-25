<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\Triggers;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function in_array;
use function strlen;

/**
 * Triggers management.
 */
class TriggersController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, string $db, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template, $db);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $db, $table, $tables, $num_tables, $total_num_tables, $sub_part;
        global $tooltip_truename, $tooltip_aliasname, $pos;
        global $errors, $urlParams, $errorUrl, $cfg;

        $this->addScriptFiles(['database/triggers.js']);

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

        $triggers = new Triggers($this->dbi, $this->template, $this->response);
        $triggers->main();
    }
}
