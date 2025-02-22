<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\View;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function explode;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function str_contains;
use function substr;

/**
 * Handles creation of VIEWs.
 */
class CreateController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $text_dir, $urlParams, $view_algorithm_options, $view_with_options, $view_security_options;
        global $message, $sep, $sql_query, $arr, $view_columns, $column_map, $systemDb, $pma_transformation_data;
        global $containerBuilder, $new_transformations_sql, $view, $item, $parts, $db, $cfg, $errorUrl;

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $urlParams['goto'] = Url::getFromRoute('/table/structure');
        $urlParams['back'] = Url::getFromRoute('/view/create');

        $view_algorithm_options = [
            'UNDEFINED',
            'MERGE',
            'TEMPTABLE',
        ];

        $view_with_options = [
            'CASCADED',
            'LOCAL',
        ];

        $view_security_options = [
            'DEFINER',
            'INVOKER',
        ];

        // View name is a compulsory field
        if (isset($_POST['view']['name']) && empty($_POST['view']['name'])) {
            $message = Message::error(__('View name can not be empty!'));
            $this->response->addJSON('message', $message);
            $this->response->setRequestStatus(false);

            return;
        }

        if (isset($_POST['createview']) || isset($_POST['alterview'])) {
            /**
             * Creates the view
             */
            $sep = "\r\n";

            if (isset($_POST['createview'])) {
                $sql_query = 'CREATE';
                if (isset($_POST['view']['or_replace'])) {
                    $sql_query .= ' OR REPLACE';
                }
            } else {
                $sql_query = 'ALTER';
            }

            if (isset($_POST['view']['algorithm']) && in_array($_POST['view']['algorithm'], $view_algorithm_options)) {
                $sql_query .= $sep . ' ALGORITHM = ' . $_POST['view']['algorithm'];
            }

            if (! empty($_POST['view']['definer'])) {
                if (! str_contains($_POST['view']['definer'], '@')) {
                    $sql_query .= $sep . 'DEFINER='
                        . Util::backquote($_POST['view']['definer']);
                } else {
                    $arr = explode('@', $_POST['view']['definer']);
                    $sql_query .= $sep . 'DEFINER=' . Util::backquote($arr[0]);
                    $sql_query .= '@' . Util::backquote($arr[1]) . ' ';
                }
            }

            if (
                isset($_POST['view']['sql_security'])
                && in_array($_POST['view']['sql_security'], $view_security_options)
            ) {
                $sql_query .= $sep . ' SQL SECURITY '
                    . $_POST['view']['sql_security'];
            }

            $sql_query .= $sep . ' VIEW '
                . Util::backquote($_POST['view']['name']);

            if (! empty($_POST['view']['column_names'])) {
                $sql_query .= $sep . ' (' . $_POST['view']['column_names'] . ')';
            }

            $sql_query .= $sep . ' AS ' . $_POST['view']['as'];

            if (isset($_POST['view']['with']) && in_array($_POST['view']['with'], $view_with_options)) {
                $sql_query .= $sep . ' WITH ' . $_POST['view']['with'] . '  CHECK OPTION';
            }

            if (! $this->dbi->tryQuery($sql_query)) {
                if (! isset($_POST['ajax_dialog'])) {
                    $message = Message::rawError($this->dbi->getError());

                    return;
                }

                $this->response->addJSON(
                    'message',
                    Message::error(
                        '<i>' . htmlspecialchars($sql_query) . '</i><br><br>'
                        . $this->dbi->getError()
                    )
                );
                $this->response->setRequestStatus(false);

                return;
            }

            // If different column names defined for VIEW
            $view_columns = [];
            if (isset($_POST['view']['column_names']) && $_POST['view']['column_names'] !== '') {
                $view_columns = explode(',', $_POST['view']['column_names']);
            }

            $column_map = $this->dbi->getColumnMapFromSql($_POST['view']['as'], $view_columns);

            $systemDb = $this->dbi->getSystemDatabase();
            $pma_transformation_data = $systemDb->getExistingTransformationData($db);

            if ($pma_transformation_data !== false) {
                // SQL for store new transformation details of VIEW
                $new_transformations_sql = $systemDb->getNewTransformationDataSql(
                    $pma_transformation_data,
                    $column_map,
                    $_POST['view']['name'],
                    $db
                );

                // Store new transformations
                if ($new_transformations_sql != '') {
                    $this->dbi->tryQuery($new_transformations_sql);
                }
            }

            unset($pma_transformation_data);

            if (! isset($_POST['ajax_dialog'])) {
                $message = Message::success();
                /** @var StructureController $controller */
                $controller = $containerBuilder->get(StructureController::class);
                $controller();
            } else {
                $this->response->addJSON(
                    'message',
                    Generator::getMessage(
                        Message::success(),
                        $sql_query
                    )
                );
                $this->response->setRequestStatus(true);
            }

            return;
        }

        $sql_query = ! empty($_POST['sql_query']) ? $_POST['sql_query'] : '';

        // prefill values if not already filled from former submission
        $view = [
            'operation' => 'create',
            'or_replace' => '',
            'algorithm' => '',
            'definer' => '',
            'sql_security' => '',
            'name' => '',
            'column_names' => '',
            'as' => $sql_query,
            'with' => '',
        ];

        // Used to prefill the fields when editing a view
        if (isset($_GET['db'], $_GET['table'])) {
            $item = $this->dbi->fetchSingleRow(
                sprintf(
                    "SELECT `VIEW_DEFINITION`, `CHECK_OPTION`, `DEFINER`,
            `SECURITY_TYPE`
            FROM `INFORMATION_SCHEMA`.`VIEWS`
            WHERE TABLE_SCHEMA='%s'
            AND TABLE_NAME='%s';",
                    $this->dbi->escapeString($_GET['db']),
                    $this->dbi->escapeString($_GET['table'])
                )
            );
            $createView = $this->dbi->getTable($_GET['db'], $_GET['table'])
                ->showCreate();

            // CREATE ALGORITHM=<ALGORITHM> DE...
            $parts = explode(' ', substr($createView, 17));
            $item['ALGORITHM'] = $parts[0];

            $view['operation'] = 'alter';
            $view['definer'] = $item['DEFINER'];
            $view['sql_security'] = $item['SECURITY_TYPE'];
            $view['name'] = $_GET['table'];
            $view['as'] = $item['VIEW_DEFINITION'];
            $view['with'] = $item['CHECK_OPTION'];
            $view['algorithm'] = $item['ALGORITHM'];

            // MySQL 8.0+ - issue #16194
            if (empty($view['as']) && is_string($createView)) {
                $parser = new Parser($createView);
                /**
                 * @var CreateStatement $stmt
                 */
                $stmt = $parser->statements[0];
                $view['as'] = isset($stmt->body) ? TokensList::build($stmt->body) : $view['as'];
            }
        }

        if (isset($_POST['view']) && is_array($_POST['view'])) {
            $view = array_merge($view, $_POST['view']);
        }

        $urlParams['db'] = $db;
        $urlParams['reload'] = 1;

        echo $this->template->render('view_create', [
            'ajax_dialog' => isset($_POST['ajax_dialog']),
            'text_dir' => $text_dir,
            'url_params' => $urlParams,
            'view' => $view,
            'view_algorithm_options' => $view_algorithm_options,
            'view_with_options' => $view_with_options,
            'view_security_options' => $view_security_options,
        ]);
    }
}
