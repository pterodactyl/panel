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

use function count;
use function is_array;
use function is_numeric;
use function json_decode;
use function min;

/**
 * Displays index edit/creation form and handles it.
 */
class IndexesController extends AbstractController
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
            $this->indexes->doSaveData($index, false, $this->db, $this->table);

            return;
        }

        $this->displayForm($index);
    }

    /**
     * Display the form to edit/create an index
     *
     * @param Index $index An Index instance.
     */
    private function displayForm(Index $index): void
    {
        $this->dbi->selectDb($GLOBALS['db']);
        $add_fields = 0;
        if (isset($_POST['index']) && is_array($_POST['index'])) {
            // coming already from form
            if (isset($_POST['index']['columns']['names'])) {
                $add_fields = count($_POST['index']['columns']['names'])
                    - $index->getColumnCount();
            }

            if (isset($_POST['add_fields'])) {
                $add_fields += $_POST['added_fields'];
            }
        } elseif (isset($_POST['create_index'])) {
            /**
             * In most cases, an index may consist of up to 16 columns, so add an initial limit.
             * More columns could be added later if necessary.
             *
             * @see https://dev.mysql.com/doc/refman/5.6/en/multiple-column-indexes.html "up to 16 columns"
             * @see https://mariadb.com/kb/en/innodb-limitations/#limitations-on-schema "maximum of 16 columns"
             * @see https://mariadb.com/kb/en/myisam-overview/#myisam-features "Maximum of 32 columns per index"
             */
            $add_fields = 1;
            if (is_numeric($_POST['added_fields']) && $_POST['added_fields'] >= 2) {
                $add_fields = min((int) $_POST['added_fields'], 16);
            }
        }

        // Get fields and stores their name/type
        if (isset($_POST['create_edit_table'])) {
            $fields = json_decode($_POST['columns'], true);
            $index_params = [
                'Non_unique' => $_POST['index']['Index_choice'] === 'UNIQUE'
                    ? '0' : '1',
            ];
            $index->set($index_params);
            $add_fields = count($fields);
        } else {
            $fields = $this->dbi->getTable($this->db, $this->table)
                ->getNameAndTypeOfTheColumns();
        }

        $form_params = [
            'db' => $this->db,
            'table' => $this->table,
        ];

        if (isset($_POST['create_index'])) {
            $form_params['create_index'] = 1;
        } elseif (isset($_POST['old_index'])) {
            $form_params['old_index'] = $_POST['old_index'];
        } elseif (isset($_POST['index'])) {
            $form_params['old_index'] = $_POST['index'];
        }

        $this->addScriptFiles(['indexes.js']);

        $this->render('table/index_form', [
            'fields' => $fields,
            'index' => $index,
            'form_params' => $form_params,
            'add_fields' => $add_fields,
            'create_edit_table' => isset($_POST['create_edit_table']),
            'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
            'is_from_nav' => isset($_POST['is_from_nav']),
        ]);
    }
}
