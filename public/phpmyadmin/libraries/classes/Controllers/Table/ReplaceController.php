<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\SqlController as DatabaseSqlController;
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Controllers\Table\SqlController as TableSqlController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function array_values;
use function class_exists;
use function count;
use function implode;
use function in_array;
use function is_file;
use function is_numeric;
use function method_exists;
use function parse_str;
use function sprintf;

/**
 * Manipulation of table data like inserting, replacing and updating.
 */
final class ReplaceController extends AbstractController
{
    /** @var InsertEdit */
    private $insertEdit;

    /** @var Transformations */
    private $transformations;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        InsertEdit $insertEdit,
        Transformations $transformations,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->insertEdit = $insertEdit;
        $this->transformations = $transformations;
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $containerBuilder, $db, $table, $urlParams, $message;
        global $errorUrl, $mime_map, $unsaved_values, $active_page, $disp_query, $disp_message;
        global $goto_include, $loop_array, $using_key, $is_insert, $is_insertignore, $query;
        global $value_sets, $func_no_param, $func_optional_param, $gis_from_text_functions, $gis_from_wkb_functions;
        global $query_fields, $insert_errors, $row_skipped, $query_values;
        global $total_affected_rows, $last_messages, $warning_messages, $error_messages, $return_to_sql_query;

        Util::checkParameters(['db', 'table', 'goto']);

        $this->dbi->selectDb($db);

        /**
         * Initializes some variables
         */
        $goto_include = false;

        $this->addScriptFiles(['makegrid.js', 'sql.js', 'indexes.js', 'gis_data_editor.js']);

        $insertRows = $_POST['insert_rows'] ?? null;
        if (is_numeric($insertRows) && $insertRows != $GLOBALS['cfg']['InsertRows']) {
            // check whether insert row mode, if so include /table/change
            $this->addScriptFiles([
                'vendor/jquery/additional-methods.js',
                'table/change.js',
            ]);
            $GLOBALS['cfg']['InsertRows'] = $_POST['insert_rows'];
            /** @var ChangeController $controller */
            $controller = $containerBuilder->get(ChangeController::class);
            $controller();

            return;
        }

        $after_insert_actions = [
            'new_insert',
            'same_insert',
            'edit_next',
        ];
        if (isset($_POST['after_insert']) && in_array($_POST['after_insert'], $after_insert_actions)) {
            $urlParams['after_insert'] = $_POST['after_insert'];
            if (isset($_POST['where_clause'])) {
                foreach ($_POST['where_clause'] as $one_where_clause) {
                    if ($_POST['after_insert'] === 'same_insert') {
                        $urlParams['where_clause'][] = $one_where_clause;
                    } elseif ($_POST['after_insert'] === 'edit_next') {
                        $this->insertEdit->setSessionForEditNext($one_where_clause);
                    }
                }
            }
        }

        //get $goto_include for different cases
        $goto_include = $this->insertEdit->getGotoInclude($goto_include);

        // Defines the url to return in case of failure of the query
        $errorUrl = $this->insertEdit->getErrorUrl($urlParams);

        /**
         * Prepares the update/insert of a row
         */
        [
            $loop_array,
            $using_key,
            $is_insert,
            $is_insertignore,
        ] = $this->insertEdit->getParamsForUpdateOrInsert();

        $query = [];
        $value_sets = [];
        $func_no_param = [
            'CONNECTION_ID',
            'CURRENT_USER',
            'CURDATE',
            'CURTIME',
            'CURRENT_DATE',
            'CURRENT_TIME',
            'DATABASE',
            'LAST_INSERT_ID',
            'NOW',
            'PI',
            'RAND',
            'SYSDATE',
            'UNIX_TIMESTAMP',
            'USER',
            'UTC_DATE',
            'UTC_TIME',
            'UTC_TIMESTAMP',
            'UUID',
            'UUID_SHORT',
            'VERSION',
        ];
        $func_optional_param = [
            'RAND',
            'UNIX_TIMESTAMP',
        ];

        $gis_from_text_functions = [
            'GeomFromText',
            'GeomCollFromText',
            'LineFromText',
            'MLineFromText',
            'PointFromText',
            'MPointFromText',
            'PolyFromText',
            'MPolyFromText',
        ];
        $gis_from_wkb_functions = [
            'GeomFromWKB',
            'GeomCollFromWKB',
            'LineFromWKB',
            'MLineFromWKB',
            'PointFromWKB',
            'MPointFromWKB',
            'PolyFromWKB',
            'MPolyFromWKB',
        ];
        if ($this->dbi->getVersion() >= 50600) {
            $gis_from_text_functions = [
                'ST_GeomFromText',
                'ST_GeomCollFromText',
                'ST_LineFromText',
                'ST_MLineFromText',
                'ST_PointFromText',
                'ST_MPointFromText',
                'ST_PolyFromText',
                'ST_MPolyFromText',
            ];
            $gis_from_wkb_functions = [
                'ST_GeomFromWKB',
                'ST_GeomCollFromWKB',
                'ST_LineFromWKB',
                'ST_MLineFromWKB',
                'ST_PointFromWKB',
                'ST_MPointFromWKB',
                'ST_PolyFromWKB',
                'ST_MPolyFromWKB',
            ];
        }

        $mime_map = $this->transformations->getMime($db, $table);
        if ($mime_map === null) {
            $mime_map = [];
        }

        $query_fields = [];
        $insert_errors = [];
        $row_skipped = false;
        $unsaved_values = [];
        foreach ($loop_array as $rownumber => $where_clause) {
            // skip fields to be ignored
            if (! $using_key && isset($_POST['insert_ignore_' . $where_clause])) {
                continue;
            }

            // Defines the SET part of the sql query
            $query_values = [];

            // Map multi-edit keys to single-level arrays, dependent on how we got the fields
            $multi_edit_columns = $_POST['fields']['multi_edit'][$rownumber] ?? [];
            $multi_edit_columns_name = $_POST['fields_name']['multi_edit'][$rownumber] ?? [];
            $multi_edit_columns_prev = $_POST['fields_prev']['multi_edit'][$rownumber] ?? null;
            $multi_edit_funcs = $_POST['funcs']['multi_edit'][$rownumber] ?? null;
            $multi_edit_salt = $_POST['salt']['multi_edit'][$rownumber] ?? null;
            $multi_edit_columns_type = $_POST['fields_type']['multi_edit'][$rownumber] ?? null;
            $multi_edit_columns_null = $_POST['fields_null']['multi_edit'][$rownumber] ?? null;
            $multi_edit_columns_null_prev = $_POST['fields_null_prev']['multi_edit'][$rownumber] ?? null;
            $multi_edit_auto_increment = $_POST['auto_increment']['multi_edit'][$rownumber] ?? null;
            $multi_edit_virtual = $_POST['virtual']['multi_edit'][$rownumber] ?? null;

            // When a select field is nullified, it's not present in $_POST
            // so initialize it; this way, the foreach($multi_edit_columns) will process it
            foreach (array_keys($multi_edit_columns_name) as $key) {
                if (isset($multi_edit_columns[$key])) {
                    continue;
                }

                $multi_edit_columns[$key] = '';
            }

            // Iterate in the order of $multi_edit_columns_name,
            // not $multi_edit_columns, to avoid problems
            // when inserting multiple entries
            $insert_fail = false;
            foreach ($multi_edit_columns_name as $key => $column_name) {
                $current_value = $multi_edit_columns[$key];
                // Note: $key is an md5 of the fieldname. The actual fieldname is
                // available in $multi_edit_columns_name[$key]

                $file_to_insert = new File();
                $file_to_insert->checkTblChangeForm((string) $key, (string) $rownumber);

                $possibly_uploaded_val = $file_to_insert->getContent();
                if ($possibly_uploaded_val !== false) {
                    $current_value = $possibly_uploaded_val;
                }

                // Apply Input Transformation if defined
                if (! empty($mime_map[$column_name]) && ! empty($mime_map[$column_name]['input_transformation'])) {
                    $filename = 'libraries/classes/Plugins/Transformations/'
                        . $mime_map[$column_name]['input_transformation'];
                    if (is_file(ROOT_PATH . $filename)) {
                        $classname = $this->transformations->getClassName($filename);
                        if (class_exists($classname)) {
                            /** @var IOTransformationsPlugin $transformation_plugin */
                            $transformation_plugin = new $classname();
                            $transformation_options = $this->transformations->getOptions(
                                $mime_map[$column_name]['input_transformation_options']
                            );
                            $current_value = $transformation_plugin->applyTransformation(
                                $current_value,
                                $transformation_options
                            );
                            // check if transformation was successful or not
                            // and accordingly set error messages & insert_fail
                            if (
                                method_exists($transformation_plugin, 'isSuccess')
                                && ! $transformation_plugin->isSuccess()
                            ) {
                                $insert_fail = true;
                                $row_skipped = true;
                                $insert_errors[] = sprintf(
                                    __('Row: %1$s, Column: %2$s, Error: %3$s'),
                                    $rownumber,
                                    $column_name,
                                    $transformation_plugin->getError()
                                );
                            }
                        }
                    }
                }

                if ($file_to_insert->isError()) {
                    $insert_errors[] = $file_to_insert->getError();
                }

                // delete $file_to_insert temporary variable
                $file_to_insert->cleanUp();

                if (empty($multi_edit_funcs[$key])) {
                    $current_value_as_an_array = $this->insertEdit->getCurrentValueForDifferentTypes(
                        $possibly_uploaded_val,
                        $key,
                        $multi_edit_columns_type,
                        $current_value,
                        $multi_edit_auto_increment,
                        $rownumber,
                        $multi_edit_columns_name,
                        $multi_edit_columns_null,
                        $multi_edit_columns_null_prev,
                        $is_insert,
                        $using_key,
                        $where_clause,
                        $table,
                        $multi_edit_funcs
                    );
                } else {
                    $current_value_as_an_array = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
                        $multi_edit_funcs,
                        $multi_edit_salt,
                        $gis_from_text_functions,
                        $current_value,
                        $gis_from_wkb_functions,
                        $func_optional_param,
                        $func_no_param,
                        $key
                    );
                }

                if (! isset($multi_edit_virtual, $multi_edit_virtual[$key])) {
                    [
                        $query_values,
                        $query_fields,
                    ] = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
                        $multi_edit_columns_name,
                        $multi_edit_columns_null,
                        $current_value,
                        $multi_edit_columns_prev,
                        $multi_edit_funcs,
                        $is_insert,
                        $query_values,
                        $query_fields,
                        $current_value_as_an_array,
                        $value_sets,
                        $key,
                        $multi_edit_columns_null_prev
                    );
                }

                if (! isset($multi_edit_columns_null[$key])) {
                    continue;
                }

                $multi_edit_columns[$key] = null;
            }

            // temporarily store rows not inserted
            // so that they can be populated again.
            if ($insert_fail) {
                $unsaved_values[$rownumber] = $multi_edit_columns;
            }

            if ($insert_fail || count($query_values) <= 0) {
                continue;
            }

            if ($is_insert) {
                $value_sets[] = implode(', ', $query_values);
            } else {
                // build update query
                $clauseIsUnique = $_POST['clause_is_unique'] ?? $_GET['clause_is_unique'] ?? '';// Should contain 0 or 1
                $query[] = 'UPDATE ' . Util::backquote($table)
                    . ' SET ' . implode(', ', $query_values)
                    . ' WHERE ' . $where_clause
                    . ($clauseIsUnique ? '' : ' LIMIT 1');
            }
        }

        unset(
            $multi_edit_columns_name,
            $multi_edit_columns_prev,
            $multi_edit_funcs,
            $multi_edit_columns_type,
            $multi_edit_columns_null,
            $func_no_param,
            $multi_edit_auto_increment,
            $current_value_as_an_array,
            $key,
            $current_value,
            $loop_array,
            $where_clause,
            $using_key,
            $multi_edit_columns_null_prev,
            $insert_fail
        );

        // Builds the sql query
        if ($is_insert && count($value_sets) > 0) {
            $query = $this->insertEdit->buildSqlQuery($is_insertignore, $query_fields, $value_sets);
        } elseif (empty($query) && ! isset($_POST['preview_sql']) && ! $row_skipped) {
            // No change -> move back to the calling script
            //
            // Note: logic passes here for inline edit
            $message = Message::success(__('No change'));
            // Avoid infinite recursion
            if ($goto_include === '/table/replace') {
                $goto_include = '/table/change';
            }

            $active_page = $goto_include;

            if ($goto_include === '/sql') {
                /** @var SqlController $controller */
                $controller = $containerBuilder->get(SqlController::class);
                $controller();

                return;
            }

            if ($goto_include === '/database/sql') {
                /** @var DatabaseSqlController $controller */
                $controller = $containerBuilder->get(DatabaseSqlController::class);
                $controller();

                return;
            }

            if ($goto_include === '/table/change') {
                /** @var ChangeController $controller */
                $controller = $containerBuilder->get(ChangeController::class);
                $controller();

                return;
            }

            if ($goto_include === '/table/sql') {
                /** @var TableSqlController $controller */
                $controller = $containerBuilder->get(TableSqlController::class);
                $controller();

                return;
            }

            /** @psalm-suppress UnresolvableInclude */
            include ROOT_PATH . Core::securePath($goto_include);

            return;
        }

        unset($multi_edit_columns, $is_insertignore);

        // If there is a request for SQL previewing.
        if (isset($_POST['preview_sql'])) {
            Core::previewSQL($query);

            return;
        }

        /**
         * Executes the sql query and get the result, then move back to the calling
         * page
         */
        [
            $urlParams,
            $total_affected_rows,
            $last_messages,
            $warning_messages,
            $error_messages,
            $return_to_sql_query,
        ] = $this->insertEdit->executeSqlQuery($urlParams, $query);

        if ($is_insert && (count($value_sets) > 0 || $row_skipped)) {
            $message = Message::getMessageForInsertedRows($total_affected_rows);
            $unsaved_values = array_values($unsaved_values);
        } else {
            $message = Message::getMessageForAffectedRows($total_affected_rows);
        }

        if ($row_skipped) {
            $goto_include = '/table/change';
            $message->addMessagesString($insert_errors, '<br>');
            $message->isError(true);
        }

        $message->addMessages($last_messages, '<br>');

        if (! empty($warning_messages)) {
            $message->addMessagesString($warning_messages, '<br>');
            $message->isError(true);
        }

        if (! empty($error_messages)) {
            $message->addMessagesString($error_messages);
            $message->isError(true);
        }

        unset(
            $error_messages,
            $warning_messages,
            $total_affected_rows,
            $last_messages,
            $row_skipped,
            $insert_errors
        );

        /**
         * The following section only applies to grid editing.
         * However, verifying isAjax() is not enough to ensure we are coming from
         * grid editing. If we are coming from the Edit or Copy link in Browse mode,
         * ajax_page_request is present in the POST parameters.
         */
        if ($this->response->isAjax() && ! isset($_POST['ajax_page_request'])) {
            /**
             * If we are in grid editing, we need to process the relational and
             * transformed fields, if they were edited. After that, output the correct
             * link/transformed value and exit
             */
            if (isset($_POST['rel_fields_list']) && $_POST['rel_fields_list'] != '') {
                $map = $this->relation->getForeigners($db, $table, '', 'both');

                /** @var array<int,array> $relation_fields */
                $relation_fields = [];
                parse_str($_POST['rel_fields_list'], $relation_fields);

                // loop for each relation cell
                foreach ($relation_fields as $cell_index => $curr_rel_field) {
                    foreach ($curr_rel_field as $relation_field => $relation_field_value) {
                        $where_comparison = "='" . $relation_field_value . "'";
                        $dispval = $this->insertEdit->getDisplayValueForForeignTableColumn(
                            $where_comparison,
                            $map,
                            $relation_field
                        );

                        $extra_data['relations'][$cell_index] = $this->insertEdit->getLinkForRelationalDisplayField(
                            $map,
                            $relation_field,
                            $where_comparison,
                            $dispval,
                            $relation_field_value
                        );
                    }
                }
            }

            if (isset($_POST['do_transformations']) && $_POST['do_transformations'] == true) {
                $edited_values = [];
                parse_str($_POST['transform_fields_list'], $edited_values);

                if (! isset($extra_data)) {
                    $extra_data = [];
                }

                $transformation_types = [
                    'input_transformation',
                    'transformation',
                ];
                foreach ($mime_map as $transformation) {
                    $column_name = $transformation['column_name'];
                    foreach ($transformation_types as $type) {
                        $file = Core::securePath($transformation[$type]);
                        $extra_data = $this->insertEdit->transformEditedValues(
                            $db,
                            $table,
                            $transformation,
                            $edited_values,
                            $file,
                            $column_name,
                            $extra_data,
                            $type
                        );
                    }
                }
            }

            // Need to check the inline edited value can be truncated by MySQL
            // without informing while saving
            $column_name = $_POST['fields_name']['multi_edit'][0][0];

            $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
                $db,
                $table,
                $column_name,
                $extra_data
            );

            /**Get the total row count of the table*/
            $_table = new Table($_POST['table'], $_POST['db']);
            $extra_data['row_count'] = $_table->countRecords();

            $extra_data['sql_query'] = Generator::getMessage($message, $GLOBALS['display_query']);

            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON('message', $message);
            $this->response->addJSON($extra_data);

            return;
        }

        if (! empty($return_to_sql_query)) {
            $disp_query = $GLOBALS['sql_query'];
            $disp_message = $message;
            unset($message);
            $GLOBALS['sql_query'] = $return_to_sql_query;
        }

        $this->addScriptFiles(['vendor/jquery/additional-methods.js', 'table/change.js']);

        $active_page = $goto_include;

        /**
         * If user asked for "and then Insert another new row" we have to remove
         * WHERE clause information so that /table/change does not go back
         * to the current record
         */
        if (isset($_POST['after_insert']) && $_POST['after_insert'] === 'new_insert') {
            unset($_POST['where_clause']);
        }

        if ($goto_include === '/sql') {
            /** @var SqlController $controller */
            $controller = $containerBuilder->get(SqlController::class);
            $controller();

            return;
        }

        if ($goto_include === '/database/sql') {
            /** @var DatabaseSqlController $controller */
            $controller = $containerBuilder->get(DatabaseSqlController::class);
            $controller();

            return;
        }

        if ($goto_include === '/table/change') {
            /** @var ChangeController $controller */
            $controller = $containerBuilder->get(ChangeController::class);
            $controller();

            return;
        }

        if ($goto_include === '/table/sql') {
            /** @var TableSqlController $controller */
            $controller = $containerBuilder->get(TableSqlController::class);
            $controller();

            return;
        }

        /**
         * Load target page.
         */
        /** @psalm-suppress UnresolvableInclude */
        require ROOT_PATH . Core::securePath($goto_include);
    }
}
