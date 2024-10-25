<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function implode;
use function in_array;
use function is_array;
use function mb_strpos;
use function sprintf;
use function strlen;

final class SaveController extends AbstractController
{
    /** @var Table  The table object */
    private $tableObj;

    /** @var Relation */
    private $relation;

    /** @var Transformations */
    private $transformations;

    /** @var DatabaseInterface */
    private $dbi;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        Relation $relation,
        Transformations $transformations,
        DatabaseInterface $dbi,
        StructureController $structureController
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->relation = $relation;
        $this->transformations = $transformations;
        $this->dbi = $dbi;
        $this->structureController = $structureController;

        $this->tableObj = $this->dbi->getTable($this->db, $this->table);
    }

    public function __invoke(): void
    {
        $regenerate = $this->updateColumns();
        if (! $regenerate) {
            // continue to show the table's structure
            unset($_POST['selected']);
        }

        ($this->structureController)();
    }

    /**
     * Update the table's structure based on $_REQUEST
     *
     * @return bool true if error occurred
     */
    private function updateColumns(): bool
    {
        $err_url = Url::getFromRoute('/table/structure', [
            'db' => $this->db,
            'table' => $this->table,
        ]);
        $regenerate = false;
        $field_cnt = count($_POST['field_name'] ?? []);
        $changes = [];
        $adjust_privileges = [];
        $columns_with_index = $this->dbi
            ->getTable($this->db, $this->table)
            ->getColumnsWithIndex(Index::PRIMARY | Index::UNIQUE);
        for ($i = 0; $i < $field_cnt; $i++) {
            if (! $this->columnNeedsAlterTable($i)) {
                continue;
            }

            $changes[] = 'CHANGE ' . Table::generateAlter(
                Util::getValueByKey($_POST, 'field_orig.' . $i, ''),
                $_POST['field_name'][$i],
                $_POST['field_type'][$i],
                $_POST['field_length'][$i],
                $_POST['field_attribute'][$i],
                Util::getValueByKey($_POST, 'field_collation.' . $i, ''),
                Util::getValueByKey($_POST, 'field_null.' . $i, 'NO'),
                $_POST['field_default_type'][$i],
                $_POST['field_default_value'][$i],
                Util::getValueByKey($_POST, 'field_extra.' . $i, false),
                Util::getValueByKey($_POST, 'field_comments.' . $i, ''),
                Util::getValueByKey($_POST, 'field_virtuality.' . $i, ''),
                Util::getValueByKey($_POST, 'field_expression.' . $i, ''),
                Util::getValueByKey($_POST, 'field_move_to.' . $i, ''),
                $columns_with_index
            );

            // find the remembered sort expression
            $sorted_col = $this->tableObj->getUiProp(Table::PROP_SORTED_COLUMN);
            // if the old column name is part of the remembered sort expression
            if (mb_strpos((string) $sorted_col, Util::backquote($_POST['field_orig'][$i])) !== false) {
                // delete the whole remembered sort expression
                $this->tableObj->removeUiProp(Table::PROP_SORTED_COLUMN);
            }

            if (
                ! isset($_POST['field_adjust_privileges'][$i])
                || empty($_POST['field_adjust_privileges'][$i])
                || $_POST['field_orig'][$i] == $_POST['field_name'][$i]
            ) {
                continue;
            }

            $adjust_privileges[$_POST['field_orig'][$i]] = $_POST['field_name'][$i];
        }

        if (count($changes) > 0 || isset($_POST['preview_sql'])) {
            // Builds the primary keys statements and updates the table
            $key_query = '';
            /**
             * this is a little bit more complex
             *
             * @todo if someone selects A_I when altering a column we need to check:
             *  - no other column with A_I
             *  - the column has an index, if not create one
             */

            // To allow replication, we first select the db to use
            // and then run queries on this db.
            if (! $this->dbi->selectDb($this->db)) {
                Generator::mysqlDie(
                    $this->dbi->getError(),
                    'USE ' . Util::backquote($this->db) . ';',
                    false,
                    $err_url
                );
            }

            $sql_query = 'ALTER TABLE ' . Util::backquote($this->table) . ' ';
            $sql_query .= implode(', ', $changes) . $key_query;
            if (isset($_POST['online_transaction'])) {
                $sql_query .= ', ALGORITHM=INPLACE, LOCK=NONE';
            }

            $sql_query .= ';';

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL(count($changes) > 0 ? $sql_query : '');

                exit;
            }

            $columns_with_index = $this->dbi
                ->getTable($this->db, $this->table)
                ->getColumnsWithIndex(Index::PRIMARY | Index::UNIQUE | Index::INDEX | Index::SPATIAL | Index::FULLTEXT);

            $changedToBlob = [];
            // While changing the Column Collation
            // First change to BLOB
            for ($i = 0; $i < $field_cnt; $i++) {
                if (
                    isset($_POST['field_collation'][$i], $_POST['field_collation_orig'][$i])
                    && $_POST['field_collation'][$i] !== $_POST['field_collation_orig'][$i]
                    && ! in_array($_POST['field_orig'][$i], $columns_with_index)
                ) {
                    $secondary_query = 'ALTER TABLE ' . Util::backquote($this->table)
                        . ' CHANGE ' . Util::backquote($_POST['field_orig'][$i])
                        . ' ' . Util::backquote($_POST['field_orig'][$i])
                        . ' BLOB';

                    if (isset($_POST['field_virtuality'][$i], $_POST['field_expression'][$i])) {
                        if ($_POST['field_virtuality'][$i]) {
                            $secondary_query .= ' AS (' . $_POST['field_expression'][$i] . ') '
                                . $_POST['field_virtuality'][$i];
                        }
                    }

                    $secondary_query .= ';';

                    $this->dbi->query($secondary_query);
                    $changedToBlob[$i] = true;
                } else {
                    $changedToBlob[$i] = false;
                }
            }

            // Then make the requested changes
            $result = $this->dbi->tryQuery($sql_query);

            if ($result !== false) {
                $changed_privileges = $this->adjustColumnPrivileges($adjust_privileges);

                if ($changed_privileges) {
                    $message = Message::success(
                        __(
                            'Table %1$s has been altered successfully. Privileges have been adjusted.'
                        )
                    );
                } else {
                    $message = Message::success(
                        __('Table %1$s has been altered successfully.')
                    );
                }

                $message->addParam($this->table);

                $this->response->addHTML(
                    Generator::getMessage($message, $sql_query, 'success')
                );
            } else {
                // An error happened while inserting/updating a table definition

                // Save the Original Error
                $orig_error = $this->dbi->getError();
                $changes_revert = [];

                // Change back to Original Collation and data type
                for ($i = 0; $i < $field_cnt; $i++) {
                    if (! $changedToBlob[$i]) {
                        continue;
                    }

                    $changes_revert[] = 'CHANGE ' . Table::generateAlter(
                        Util::getValueByKey($_POST, 'field_orig.' . $i, ''),
                        $_POST['field_name'][$i],
                        $_POST['field_type_orig'][$i],
                        $_POST['field_length_orig'][$i],
                        $_POST['field_attribute_orig'][$i],
                        Util::getValueByKey($_POST, 'field_collation_orig.' . $i, ''),
                        Util::getValueByKey($_POST, 'field_null_orig.' . $i, 'NO'),
                        $_POST['field_default_type_orig'][$i],
                        $_POST['field_default_value_orig'][$i],
                        Util::getValueByKey($_POST, 'field_extra_orig.' . $i, false),
                        Util::getValueByKey($_POST, 'field_comments_orig.' . $i, ''),
                        Util::getValueByKey($_POST, 'field_virtuality_orig.' . $i, ''),
                        Util::getValueByKey($_POST, 'field_expression_orig.' . $i, ''),
                        Util::getValueByKey($_POST, 'field_move_to_orig.' . $i, '')
                    );
                }

                $revert_query = 'ALTER TABLE ' . Util::backquote($this->table)
                    . ' ';
                $revert_query .= implode(', ', $changes_revert) . '';
                $revert_query .= ';';

                // Column reverted back to original
                $this->dbi->query($revert_query);

                $this->response->setRequestStatus(false);
                $message = Message::rawError(
                    __('Query error') . ':<br>' . $orig_error
                );
                $this->response->addHTML(
                    Generator::getMessage($message, $sql_query, 'error')
                );
                $regenerate = true;
            }
        }

        // update field names in relation
        if (isset($_POST['field_orig']) && is_array($_POST['field_orig'])) {
            foreach ($_POST['field_orig'] as $fieldindex => $fieldcontent) {
                if ($_POST['field_name'][$fieldindex] == $fieldcontent) {
                    continue;
                }

                $this->relation->renameField($this->db, $this->table, $fieldcontent, $_POST['field_name'][$fieldindex]);
            }
        }

        // update mime types
        if (isset($_POST['field_mimetype']) && is_array($_POST['field_mimetype']) && $GLOBALS['cfg']['BrowseMIME']) {
            foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                if (! isset($_POST['field_name'][$fieldindex]) || strlen($_POST['field_name'][$fieldindex]) <= 0) {
                    continue;
                }

                $this->transformations->setMime(
                    $this->db,
                    $this->table,
                    $_POST['field_name'][$fieldindex],
                    $mimetype,
                    $_POST['field_transformation'][$fieldindex],
                    $_POST['field_transformation_options'][$fieldindex],
                    $_POST['field_input_transformation'][$fieldindex],
                    $_POST['field_input_transformation_options'][$fieldindex]
                );
            }
        }

        return $regenerate;
    }

    /**
     * Verifies if some elements of a column have changed
     *
     * @param int $i column index in the request
     */
    private function columnNeedsAlterTable($i): bool
    {
        // these two fields are checkboxes so might not be part of the
        // request; therefore we define them to avoid notices below
        if (! isset($_POST['field_null'][$i])) {
            $_POST['field_null'][$i] = 'NO';
        }

        if (! isset($_POST['field_extra'][$i])) {
            $_POST['field_extra'][$i] = '';
        }

        // field_name does not follow the convention (corresponds to field_orig)
        if ($_POST['field_name'][$i] != $_POST['field_orig'][$i]) {
            return true;
        }

        $fields = [
            'field_attribute',
            'field_collation',
            'field_comments',
            'field_default_value',
            'field_default_type',
            'field_extra',
            'field_length',
            'field_null',
            'field_type',
        ];
        foreach ($fields as $field) {
            if ($_POST[$field][$i] != $_POST[$field . '_orig'][$i]) {
                return true;
            }
        }

        return ! empty($_POST['field_move_to'][$i]);
    }

    /**
     * Adjusts the Privileges for all the columns whose names have changed
     *
     * @param array $adjust_privileges assoc array of old col names mapped to new
     *                                 cols
     */
    private function adjustColumnPrivileges(array $adjust_privileges): bool
    {
        $changed = false;

        if (
            Util::getValueByKey($GLOBALS, 'col_priv', false)
            && Util::getValueByKey($GLOBALS, 'is_reload_priv', false)
        ) {
            $this->dbi->selectDb('mysql');

            // For Column specific privileges
            foreach ($adjust_privileges as $oldCol => $newCol) {
                $this->dbi->query(
                    sprintf(
                        'UPDATE %s SET Column_name = "%s"
                        WHERE Db = "%s"
                        AND Table_name = "%s"
                        AND Column_name = "%s";',
                        Util::backquote('columns_priv'),
                        $newCol,
                        $this->db,
                        $this->table,
                        $oldCol
                    )
                );

                // i.e. if atleast one column privileges adjusted
                $changed = true;
            }

            if ($changed) {
                // Finally FLUSH the new privileges
                $this->dbi->query('FLUSH PRIVILEGES;');
            }
        }

        return $changed;
    }
}
