<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function _pgettext;
use function in_array;
use function intval;
use function json_decode;
use function json_encode;
use function min;

/**
 * Normalization process (temporarily specific to 1NF).
 */
class NormalizationController extends AbstractController
{
    /** @var Normalization */
    private $normalization;

    public function __construct(ResponseRenderer $response, Template $template, Normalization $normalization)
    {
        parent::__construct($response, $template);
        $this->normalization = $normalization;
    }

    public function __invoke(): void
    {
        global $db, $table;

        if (isset($_POST['getColumns'])) {
            $html = '<option selected disabled>' . __('Select one…') . '</option>'
                . '<option value="no_such_col">' . __('No such column') . '</option>';
            //get column whose datatype falls under string category
            $html .= $this->normalization->getHtmlForColumnsList(
                $db,
                $table,
                _pgettext('string types', 'String')
            );
            echo $html;

            return;
        }

        if (isset($_POST['splitColumn'])) {
            $num_fields = min(4096, intval($_POST['numFields']));
            $html = $this->normalization->getHtmlForCreateNewColumn($num_fields, $db, $table);
            $html .= Url::getHiddenInputs($db, $table);
            echo $html;

            return;
        }

        if (isset($_POST['addNewPrimary'])) {
            $num_fields = 1;
            $columnMeta = [
                'Field' => $table . '_id',
                'Extra' => 'auto_increment',
            ];
            $html = $this->normalization->getHtmlForCreateNewColumn($num_fields, $db, $table, $columnMeta);
            $html .= Url::getHiddenInputs($db, $table);
            echo $html;

            return;
        }

        if (isset($_POST['findPdl'])) {
            $html = $this->normalization->findPartialDependencies($table, $db);
            echo $html;

            return;
        }

        if (isset($_POST['getNewTables2NF'])) {
            $partialDependencies = json_decode($_POST['pd'], true);
            $html = $this->normalization->getHtmlForNewTables2NF($partialDependencies, $table);
            echo $html;

            return;
        }

        if (isset($_POST['getNewTables3NF'])) {
            $dependencies = json_decode($_POST['pd']);
            $tables = json_decode($_POST['tables'], true);
            $newTables = $this->normalization->getHtmlForNewTables3NF($dependencies, $tables, $db);
            $this->response->disable();
            Core::headerJSON();
            echo json_encode($newTables);

            return;
        }

        $this->addScriptFiles(['normalization.js', 'vendor/jquery/jquery.uitablefilter.js']);

        $normalForm = '1nf';
        if (isset($_POST['normalizeTo']) && in_array($_POST['normalizeTo'], ['1nf', '2nf', '3nf'])) {
            $normalForm = $_POST['normalizeTo'];
        }

        if (isset($_POST['createNewTables2NF'])) {
            $partialDependencies = json_decode($_POST['pd'], true);
            $tablesName = json_decode($_POST['newTablesName']);
            $res = $this->normalization->createNewTablesFor2NF($partialDependencies, $tablesName, $table, $db);
            $this->response->addJSON($res);

            return;
        }

        if (isset($_POST['createNewTables3NF'])) {
            $newtables = json_decode($_POST['newTables'], true);
            $res = $this->normalization->createNewTablesFor3NF($newtables, $db);
            $this->response->addJSON($res);

            return;
        }

        if (isset($_POST['repeatingColumns'])) {
            $repeatingColumns = $_POST['repeatingColumns'];
            $newTable = $_POST['newTable'];
            $newColumn = $_POST['newColumn'];
            $primary_columns = $_POST['primary_columns'];
            $res = $this->normalization->moveRepeatingGroup(
                $repeatingColumns,
                $primary_columns,
                $newTable,
                $newColumn,
                $table,
                $db
            );
            $this->response->addJSON($res);

            return;
        }

        if (isset($_POST['step1'])) {
            $html = $this->normalization->getHtmlFor1NFStep1($db, $table, $normalForm);
            $this->response->addHTML($html);
        } elseif (isset($_POST['step2'])) {
            $res = $this->normalization->getHtmlContentsFor1NFStep2($db, $table);
            $this->response->addJSON($res);
        } elseif (isset($_POST['step3'])) {
            $res = $this->normalization->getHtmlContentsFor1NFStep3($db, $table);
            $this->response->addJSON($res);
        } elseif (isset($_POST['step4'])) {
            $res = $this->normalization->getHtmlContentsFor1NFStep4($db, $table);
            $this->response->addJSON($res);
        } elseif (isset($_POST['step']) && $_POST['step'] == '2.1') {
            $res = $this->normalization->getHtmlFor2NFstep1($db, $table);
            $this->response->addJSON($res);
        } elseif (isset($_POST['step']) && $_POST['step'] == '3.1') {
            $tables = $_POST['tables'];
            $res = $this->normalization->getHtmlFor3NFstep1($db, $tables);
            $this->response->addJSON($res);
        } else {
            $this->response->addHTML($this->normalization->getHtmlForNormalizeTable());
        }
    }
}
