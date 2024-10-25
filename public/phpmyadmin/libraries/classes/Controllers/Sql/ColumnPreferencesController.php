<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;

final class ColumnPreferencesController extends AbstractController
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
        global $db, $table;

        $this->checkUserPrivileges->getPrivileges();

        $tableObject = $this->dbi->getTable($db, $table);
        $status = false;

        // set column order
        if (isset($_POST['col_order'])) {
            $status = $this->sql->setColumnProperty($tableObject, 'col_order');
        }

        // set column visibility
        if ($status === true && isset($_POST['col_visib'])) {
            $status = $this->sql->setColumnProperty($tableObject, 'col_visib');
        }

        if ($status instanceof Message) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $status->getString());

            return;
        }

        $this->response->setRequestStatus($status === true);
    }
}
