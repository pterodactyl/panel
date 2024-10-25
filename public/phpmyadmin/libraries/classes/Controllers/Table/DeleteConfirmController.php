<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function is_array;

final class DeleteConfirmController extends AbstractController
{
    public function __invoke(): void
    {
        global $db, $table, $sql_query, $urlParams, $errorUrl, $cfg;

        $selected = $_POST['rows_to_delete'] ?? null;

        if (! isset($selected) || ! is_array($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return;
        }

        Util::checkParameters(['db', 'table']);

        $urlParams = ['db' => $db, 'table' => $table];
        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $errorUrl .= Url::getCommon($urlParams, '&');

        DbTableExists::check();

        $this->render('table/delete/confirm', [
            'db' => $db,
            'table' => $table,
            'selected' => $selected,
            'sql_query' => $sql_query,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
        ]);
    }
}
