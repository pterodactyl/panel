<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;

final class PrimaryController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        DatabaseInterface $dbi,
        StructureController $structureController
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        global $db, $table, $message, $sql_query, $urlParams, $errorUrl, $cfg;

        $selected = $_POST['selected'] ?? [];
        $selected_fld = $_POST['selected_fld'] ?? [];

        if (empty($selected) && empty($selected_fld)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $primary = $this->getKeyForTablePrimary();
        if (empty($primary) && ! empty($selected_fld)) {
            // no primary key, so we can safely create new
            $mult_btn = __('Yes');
            $selected = $selected_fld;
        }

        $mult_btn = $_POST['mult_btn'] ?? $mult_btn ?? '';

        if (! empty($selected_fld) && ! empty($primary)) {
            Util::checkParameters(['db', 'table']);

            $urlParams = ['db' => $db, 'table' => $table];
            $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $errorUrl .= Url::getCommon($urlParams, '&');

            DbTableExists::check();

            $this->render('table/structure/primary', [
                'db' => $db,
                'table' => $table,
                'selected' => $selected_fld,
            ]);

            return;
        }

        if ($mult_btn === __('Yes')) {
            $sql_query = 'ALTER TABLE ' . Util::backquote($table);
            if (! empty($primary)) {
                $sql_query .= ' DROP PRIMARY KEY,';
            }

            $sql_query .= ' ADD PRIMARY KEY(';

            $i = 1;
            $selectedCount = count($selected);
            foreach ($selected as $field) {
                $sql_query .= Util::backquote($field);
                $sql_query .= $i++ === $selectedCount ? ');' : ', ';
            }

            $this->dbi->selectDb($db);
            $result = $this->dbi->tryQuery($sql_query);

            if (! $result) {
                $message = Message::error($this->dbi->getError());
            }
        }

        if (empty($message)) {
            $message = Message::success();
        }

        ($this->structureController)();
    }

    /**
     * Gets table primary key
     *
     * @return string
     */
    private function getKeyForTablePrimary()
    {
        $this->dbi->selectDb($this->db);
        $result = $this->dbi->query(
            'SHOW KEYS FROM ' . Util::backquote($this->table) . ';'
        );
        $primary = '';
        foreach ($result as $row) {
            // Backups the list of primary keys
            if ($row['Key_name'] !== 'PRIMARY') {
                continue;
            }

            $primary .= $row['Column_name'] . ', ';
        }

        return $primary;
    }
}
