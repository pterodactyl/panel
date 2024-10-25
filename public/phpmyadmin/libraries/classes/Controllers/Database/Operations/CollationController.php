<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Operations;

use PhpMyAdmin\Controllers\Database\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;

final class CollationController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        Operations $operations,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db);
        $this->operations = $operations;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $db, $cfg, $errorUrl;

        if (! $this->response->isAjax()) {
            return;
        }

        if (empty($_POST['db_collation'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No collation provided.')));

            return;
        }

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $sql_query = 'ALTER DATABASE ' . Util::backquote($db)
            . ' DEFAULT' . Util::getCharsetQueryPart($_POST['db_collation'] ?? '');
        $this->dbi->query($sql_query);
        $message = Message::success();

        /**
         * Changes tables charset if requested by the user
         */
        if (isset($_POST['change_all_tables_collations']) && $_POST['change_all_tables_collations'] === 'on') {
            [$tables] = Util::getDbInfo($db, '');
            foreach ($tables as $tableName => $data) {
                if ($this->dbi->getTable($db, $tableName)->isView()) {
                    // Skip views, we can not change the collation of a view.
                    // issue #15283
                    continue;
                }

                $sql_query = 'ALTER TABLE '
                    . Util::backquote($db)
                    . '.'
                    . Util::backquote($tableName)
                    . ' DEFAULT '
                    . Util::getCharsetQueryPart($_POST['db_collation'] ?? '');
                $this->dbi->query($sql_query);

                /**
                 * Changes columns charset if requested by the user
                 */
                if (
                    ! isset($_POST['change_all_tables_columns_collations']) ||
                    $_POST['change_all_tables_columns_collations'] !== 'on'
                ) {
                    continue;
                }

                $this->operations->changeAllColumnsCollation($db, $tableName, $_POST['db_collation']);
            }
        }

        $this->response->setRequestStatus($message->isSuccess());
        $this->response->addJSON('message', $message);
    }
}
