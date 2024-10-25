<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common as DesignerCommon;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function sprintf;

class DesignerController extends AbstractController
{
    /** @var Designer */
    private $databaseDesigner;

    /** @var DesignerCommon */
    private $designerCommon;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        Designer $databaseDesigner,
        DesignerCommon $designerCommon
    ) {
        parent::__construct($response, $template, $db);
        $this->databaseDesigner = $databaseDesigner;
        $this->designerCommon = $designerCommon;
    }

    public function __invoke(): void
    {
        global $db, $script_display_field, $tab_column, $tables_all_keys, $tables_pk_or_unique_keys;
        global $success, $page, $message, $display_page, $selected_page, $tab_pos, $fullTableNames, $script_tables;
        global $script_contr, $params, $tables, $num_tables, $total_num_tables, $sub_part;
        global $tooltip_truename, $tooltip_aliasname, $pos, $classes_side_menu, $cfg, $errorUrl;

        if (isset($_POST['dialog'])) {
            if ($_POST['dialog'] === 'edit') {
                $html = $this->databaseDesigner->getHtmlForEditOrDeletePages($_POST['db'], 'editPage');
            } elseif ($_POST['dialog'] === 'delete') {
                $html = $this->databaseDesigner->getHtmlForEditOrDeletePages($_POST['db'], 'deletePage');
            } elseif ($_POST['dialog'] === 'save_as') {
                $html = $this->databaseDesigner->getHtmlForPageSaveAs($_POST['db']);
            } elseif ($_POST['dialog'] === 'export') {
                $html = $this->databaseDesigner->getHtmlForSchemaExport($_POST['db'], $_POST['selected_page']);
            } elseif ($_POST['dialog'] === 'add_table') {
                // Pass the db and table to the getTablesInfo so we only have the table we asked for
                $script_display_field = $this->designerCommon->getTablesInfo($_POST['db'], $_POST['table']);
                $tab_column = $this->designerCommon->getColumnsInfo($script_display_field);
                $tables_all_keys = $this->designerCommon->getAllKeys($script_display_field);
                $tables_pk_or_unique_keys = $this->designerCommon->getPkOrUniqueKeys($script_display_field);

                $html = $this->databaseDesigner->getDatabaseTables(
                    $_POST['db'],
                    $script_display_field,
                    [],
                    -1,
                    $tab_column,
                    $tables_all_keys,
                    $tables_pk_or_unique_keys
                );
            }

            if (! empty($html)) {
                $this->response->addHTML($html);
            }

            return;
        }

        if (isset($_POST['operation'])) {
            if ($_POST['operation'] === 'deletePage') {
                $success = $this->designerCommon->deletePage($_POST['selected_page']);
                $this->response->setRequestStatus($success);
            } elseif ($_POST['operation'] === 'savePage') {
                if ($_POST['save_page'] === 'same') {
                    $page = $_POST['selected_page'];
                } elseif ($this->designerCommon->getPageExists($_POST['selected_value'])) {
                    $this->response->addJSON(
                        'message',
                        sprintf(
                            /* l10n: The user tries to save a page with an existing name in Designer */
                            __('There already exists a page named "%s" please rename it to something else.'),
                            htmlspecialchars($_POST['selected_value'])
                        )
                    );
                    $this->response->setRequestStatus(false);

                    return;
                } else {
                    $page = $this->designerCommon->createNewPage($_POST['selected_value'], $_POST['db']);
                    $this->response->addJSON('id', $page);
                }

                $success = $this->designerCommon->saveTablePositions($page);
                $this->response->setRequestStatus($success);
            } elseif ($_POST['operation'] === 'setDisplayField') {
                [
                    $success,
                    $message,
                ] = $this->designerCommon->saveDisplayField($_POST['db'], $_POST['table'], $_POST['field']);
                $this->response->setRequestStatus($success);
                $this->response->addJSON('message', $message);
            } elseif ($_POST['operation'] === 'addNewRelation') {
                [$success, $message] = $this->designerCommon->addNewRelation(
                    $_POST['db'],
                    $_POST['T1'],
                    $_POST['F1'],
                    $_POST['T2'],
                    $_POST['F2'],
                    $_POST['on_delete'],
                    $_POST['on_update'],
                    $_POST['DB1'],
                    $_POST['DB2']
                );
                $this->response->setRequestStatus($success);
                $this->response->addJSON('message', $message);
            } elseif ($_POST['operation'] === 'removeRelation') {
                [$success, $message] = $this->designerCommon->removeRelation(
                    $_POST['T1'],
                    $_POST['F1'],
                    $_POST['T2'],
                    $_POST['F2']
                );
                $this->response->setRequestStatus($success);
                $this->response->addJSON('message', $message);
            } elseif ($_POST['operation'] === 'save_setting_value') {
                $success = $this->designerCommon->saveSetting($_POST['index'], $_POST['value']);
                $this->response->setRequestStatus($success);
            }

            return;
        }

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $script_display_field = $this->designerCommon->getTablesInfo();

        $display_page = -1;
        $selected_page = null;

        $visualBuilderMode = isset($_GET['query']);

        if ($visualBuilderMode) {
            $display_page = $this->designerCommon->getDefaultPage($_GET['db']);
        } elseif (! empty($_GET['page'])) {
            $display_page = $_GET['page'];
        } else {
            $display_page = $this->designerCommon->getLoadingPage($_GET['db']);
        }

        if ($display_page != -1) {
            $selected_page = $this->designerCommon->getPageName($display_page);
        }

        $tab_pos = $this->designerCommon->getTablePositions($display_page);

        $fullTableNames = [];

        foreach ($script_display_field as $designerTable) {
            $fullTableNames[] = $designerTable->getDbTableString();
        }

        foreach ($tab_pos as $position) {
            if (in_array($position['dbName'] . '.' . $position['tableName'], $fullTableNames)) {
                continue;
            }

            $designerTables = $this->designerCommon->getTablesInfo($position['dbName'], $position['tableName']);
            foreach ($designerTables as $designerTable) {
                $script_display_field[] = $designerTable;
            }
        }

        $tab_column = $this->designerCommon->getColumnsInfo($script_display_field);
        $script_tables = $this->designerCommon->getScriptTabs($script_display_field);
        $tables_pk_or_unique_keys = $this->designerCommon->getPkOrUniqueKeys($script_display_field);
        $tables_all_keys = $this->designerCommon->getAllKeys($script_display_field);
        $classes_side_menu = $this->databaseDesigner->returnClassNamesFromMenuButtons();

        $script_contr = $this->designerCommon->getScriptContr($script_display_field);

        $params = ['lang' => $GLOBALS['lang']];
        if (isset($_GET['db'])) {
            $params['db'] = $_GET['db'];
        }

        $this->response->getFooter()->setMinimal();
        $header = $this->response->getHeader();
        $header->setBodyId('designer_body');

        $this->addScriptFiles([
            'designer/database.js',
            'designer/objects.js',
            'designer/page.js',
            'designer/history.js',
            'designer/move.js',
            'designer/init.js',
        ]);

        [
            $tables,
            $num_tables,
            $total_num_tables,
            $sub_part,,,
            $tooltip_truename,
            $tooltip_aliasname,
            $pos,
        ] = Util::getDbInfo($db, $sub_part ?? '');

        // Embed some data into HTML, later it will be read
        // by designer/init.js and converted to JS variables.
        $this->response->addHTML(
            $this->databaseDesigner->getHtmlForMain(
                $db,
                $_GET['db'],
                $script_display_field,
                $script_tables,
                $script_contr,
                $script_display_field,
                $display_page,
                $visualBuilderMode,
                $selected_page,
                $classes_side_menu,
                $tab_pos,
                $tab_column,
                $tables_all_keys,
                $tables_pk_or_unique_keys
            )
        );

        $this->response->addHTML('<div id="PMA_disable_floating_menubar"></div>');
    }
}
