<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function is_array;
use function mb_strtolower;
use function sprintf;
use function strlen;

/**
 * Displays table create form and handles it.
 */
class CreateController extends AbstractController
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
        global $num_fields, $action, $sql_query, $result, $db, $table;

        Util::checkParameters(['db']);

        $cfg = $this->config->settings;

        /* Check if database name is empty */
        if (strlen($db) === 0) {
            Generator::mysqlDie(
                __('The database name is empty!'),
                '',
                false,
                'index.php'
            );
        }

        /**
         * Selects the database to work with
         */
        if (! $this->dbi->selectDb($db)) {
            Generator::mysqlDie(
                sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
                '',
                false,
                'index.php'
            );
        }

        if ($this->dbi->getColumns($db, $table)) {
            // table exists already
            Generator::mysqlDie(
                sprintf(__('Table %s already exists!'), htmlspecialchars($table)),
                '',
                false,
                Url::getFromRoute('/database/structure', ['db' => $db])
            );
        }

        $createAddField = new CreateAddField($this->dbi);

        $num_fields = $createAddField->getNumberOfFieldsFromRequest();

        $action = Url::getFromRoute('/table/create');

        /**
         * The form used to define the structure of the table has been submitted
         */
        if (isset($_POST['do_save_data'])) {
            // lower_case_table_names=1 `DB` becomes `db`
            if ($this->dbi->getLowerCaseNames() === '1') {
                $db = mb_strtolower($db);
                $table = mb_strtolower($table);
            }

            $sql_query = $createAddField->getTableCreationQuery($db, $table);

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL($sql_query);

                return;
            }

            // Executes the query
            $result = $this->dbi->tryQuery($sql_query);

            if ($result) {
                // Update comment table for mime types [MIME]
                if (isset($_POST['field_mimetype']) && is_array($_POST['field_mimetype']) && $cfg['BrowseMIME']) {
                    foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                        if (
                            ! isset($_POST['field_name'][$fieldindex])
                            || strlen($_POST['field_name'][$fieldindex]) <= 0
                        ) {
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
            } else {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $this->dbi->getError());
            }

            return;
        }

        // Do not display the table in the header since it hasn't been created yet
        $this->response->getHeader()->getMenu()->setTable('');

        $this->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js', 'indexes.js']);

        $templateData = ColumnsDefinition::displayForm(
            $this->transformations,
            $this->relation,
            $this->dbi,
            $action,
            $num_fields
        );

        $this->render('columns_definitions/column_definitions_form', $templateData);
    }
}
