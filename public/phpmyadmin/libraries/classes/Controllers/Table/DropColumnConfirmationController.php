<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;

final class DropColumnConfirmationController extends AbstractController
{
    public function __invoke(): void
    {
        global $urlParams, $errorUrl, $cfg;

        $selected = $_POST['selected_fld'] ?? null;

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        Util::checkParameters(['db', 'table']);

        $urlParams = ['db' => $this->db, 'table' => $this->table];
        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $errorUrl .= Url::getCommon($urlParams, '&');

        DbTableExists::check();

        $this->render('table/structure/drop_confirm', [
            'db' => $this->db,
            'table' => $this->table,
            'fields' => $selected,
        ]);
    }
}
