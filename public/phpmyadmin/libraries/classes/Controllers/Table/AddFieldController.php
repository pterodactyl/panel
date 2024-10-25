<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function intval;
use function is_array;
use function min;
use function strlen;

/**
 * Displays add field form and handles it.
 */
class AddFieldController extends AbstractController
{
    /** @var Transformations */
    private $transformations;

    /** @var Config */
    private $config;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        Transformations $transformations,
        Config $config,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->transformations = $transformations;
        $this->config = $config;
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $errorUrl, $message, $action, $active_page, $sql_query;
        global $num_fields, $regenerate, $result, $db, $table;

        $this->addScriptFiles(['table/structure.js']);

        // Check parameters
        Util::checkParameters(['db', 'table']);

        $cfg = $this->config->settings;

        /**
         * Defines the url to return to in case of error in a sql statement
         */
        $errorUrl = Url::getFromRoute('/table/sql', [
            'db' => $db,
            'table' => $table,
        ]);

        // check number of fields to be created
        if (isset($_POST['submit_num_fields'])) {
            if (isset($_POST['orig_after_field'])) {
                $_POST['after_field'] = $_POST['orig_after_field'];
            }

            if (isset($_POST['orig_field_where'])) {
                $_POST['field_where'] = $_POST['orig_field_where'];
            }

            $num_fields = min(
                intval($_POST['orig_num_fields']) + intval($_POST['added_fields']),
                4096
            );
            $regenerate = true;
        } elseif (isset($_POST['num_fields']) && intval($_POST['num_fields']) > 0) {
            $num_fields = min(4096, intval($_POST['num_fields']));
        } else {
            $num_fields = 1;
        }

        if (isset($_POST['do_save_data'])) {
            // avoid an incorrect calling of PMA_updateColumns() via
            // /table/structure below
            unset($_POST['do_save_data']);

            $createAddField = new CreateAddField($this->dbi);

            $sql_query = $createAddField->getColumnCreationQuery($table);

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL($sql_query);

                return;
            }

            $result = $createAddField->tryColumnCreationQuery($db, $sql_query, $errorUrl);

            if ($result !== true) {
                $error_message_html = Generator::mysqlDie('', '', false, $errorUrl, false);
                $this->response->addHTML($error_message_html ?? '');
                $this->response->setRequestStatus(false);

                return;
            }

            // Update comment table for mime types [MIME]
            if (isset($_POST['field_mimetype']) && is_array($_POST['field_mimetype']) && $cfg['BrowseMIME']) {
                foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                    if (! isset($_POST['field_name'][$fieldindex]) || strlen($_POST['field_name'][$fieldindex]) <= 0) {
                        continue;
                    }

                    $this->transformations->setMime(
                        $db,
                        $table,
                        $_POST['field_name'][$fieldindex],
                        $mimetype,
                        $_POST['field_transformation'][$fieldindex],
                        $_POST['field_transformation_options'][$fieldindex],
                        $_POST['field_input_transformation'][$fieldindex],
                        $_POST['field_input_transformation_options'][$fieldindex]
                    );
                }
            }

            // Go back to the structure sub-page
            $message = Message::success(
                __('Table %1$s has been altered successfully.')
            );
            $message->addParam($table);
            $this->response->addJSON(
                'message',
                Generator::getMessage($message, $sql_query, 'success')
            );

            // Give an URL to call and use to appends the structure after the success message
            $this->response->addJSON(
                'structure_refresh_route',
                Url::getFromRoute('/table/structure', [
                    'db' => $db,
                    'table' => $table,
                    'ajax_request' => '1',
                ])
            );

            return;
        }

        $url_params = ['db' => $db, 'table' => $table];
        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $errorUrl .= Url::getCommon($url_params, '&');

        DbTableExists::check();

        $active_page = Url::getFromRoute('/table/structure');
        /**
         * Display the form
         */
        $action = Url::getFromRoute('/table/add-field');

        $this->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js', 'indexes.js']);

        $templateData = ColumnsDefinition::displayForm(
            $this->transformations,
            $this->relation,
            $this->dbi,
            $action,
            $num_fields,
            $regenerate
        );

        $this->render('columns_definitions/column_definitions_form', $templateData);
    }
}
