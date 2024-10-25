<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Index;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function is_array;

final class IndexRenameController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Indexes */
    private $indexes;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        DatabaseInterface $dbi,
        Indexes $indexes
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
        $this->indexes = $indexes;
    }

    public function __invoke(): void
    {
        global $db, $table, $urlParams, $cfg, $errorUrl;

        if (! isset($_POST['create_edit_table'])) {
            Util::checkParameters(['db', 'table']);

            $urlParams = ['db' => $db, 'table' => $table];
            $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $errorUrl .= Url::getCommon($urlParams, '&');

            DbTableExists::check();
        }

        if (isset($_POST['index'])) {
            if (is_array($_POST['index'])) {
                // coming already from form
                $index = new Index($_POST['index']);
            } else {
                $index = $this->dbi->getTable($this->db, $this->table)->getIndex($_POST['index']);
            }
        } else {
            $index = new Index();
        }

        if (isset($_POST['do_save_data'])) {
            $this->indexes->doSaveData($index, true, $this->db, $this->table);

            return;
        }

        $this->displayRenameForm($index);
    }

    /**
     * Display the rename form to rename an index
     *
     * @param Index $index An Index instance.
     */
    private function displayRenameForm(Index $index): void
    {
        $this->dbi->selectDb($GLOBALS['db']);

        $formParams = [
            'db' => $this->db,
            'table' => $this->table,
        ];

        if (isset($_POST['old_index'])) {
            $formParams['old_index'] = $_POST['old_index'];
        } elseif (isset($_POST['index'])) {
            $formParams['old_index'] = $_POST['index'];
        }

        $this->addScriptFiles(['indexes.js']);

        $this->render('table/index_rename_form', [
            'index' => $index,
            'form_params' => $formParams,
        ]);
    }
}
