<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function array_splice;
use function count;
use function implode;
use function in_array;
use function is_array;
use function mb_strtoupper;
use function sprintf;
use function str_replace;

final class MoveColumnsController extends AbstractController
{
    /** @var Table  The table object */
    private $tableObj;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
        $this->tableObj = $this->dbi->getTable($this->db, $this->table);
    }

    public function __invoke(): void
    {
        if (! isset($_POST['move_columns']) || ! is_array($_POST['move_columns']) || ! $this->response->isAjax()) {
            return;
        }

        $this->dbi->selectDb($this->db);

        /**
         * load the definitions for all columns
         */
        $columns = $this->dbi->getColumnsFull($this->db, $this->table);
        $column_names = array_keys($columns);
        $changes = [];

        // @see https://mariadb.com/kb/en/library/changes-improvements-in-mariadb-102/#information-schema
        $usesLiteralNull = $this->dbi->isMariaDB() && $this->dbi->getVersion() >= 100200;
        $defaultNullValue = $usesLiteralNull ? 'NULL' : null;
        // move columns from first to last
        for ($i = 0, $l = count($_POST['move_columns']); $i < $l; $i++) {
            $column = $_POST['move_columns'][$i];
            // is this column already correctly placed?
            if ($column_names[$i] == $column) {
                continue;
            }

            // it is not, let's move it to index $i
            $data = $columns[$column];
            $extracted_columnspec = Util::extractColumnSpec($data['Type']);
            if (isset($data['Extra']) && $data['Extra'] === 'on update CURRENT_TIMESTAMP') {
                $extracted_columnspec['attribute'] = $data['Extra'];
                unset($data['Extra']);
            }

            $timeType = $data['Type'] === 'timestamp' || $data['Type'] === 'datetime';
            $timeDefault = $data['Default'] === 'CURRENT_TIMESTAMP' || $data['Default'] === 'current_timestamp()';
            $current_timestamp = $timeType && $timeDefault;

            $uuidType = $data['Type'] === 'uuid';
            $uuidDefault = $data['Default'] === 'UUID' || $data['Default'] === 'uuid()';
            $uuid = $uuidType && $uuidDefault;

            // @see https://mariadb.com/kb/en/library/information-schema-columns-table/#examples
            if ($data['Null'] === 'YES' && in_array($data['Default'], [$defaultNullValue, null])) {
                $default_type = 'NULL';
            } elseif ($current_timestamp) {
                $default_type = 'CURRENT_TIMESTAMP';
            } elseif ($uuid) {
                $default_type = 'UUID';
            } elseif ($data['Default'] === null) {
                $default_type = 'NONE';
            } else {
                $default_type = 'USER_DEFINED';
            }

            $virtual = [
                'VIRTUAL',
                'PERSISTENT',
                'VIRTUAL GENERATED',
                'STORED GENERATED',
            ];
            $data['Virtuality'] = '';
            $data['Expression'] = '';
            if (isset($data['Extra']) && in_array($data['Extra'], $virtual)) {
                $data['Virtuality'] = str_replace(' GENERATED', '', $data['Extra']);
                $expressions = $this->tableObj->getColumnGenerationExpression($column);
                $data['Expression'] = is_array($expressions) ? $expressions[$column] : null;
            }

            $changes[] = 'CHANGE ' . Table::generateAlter(
                $column,
                $column,
                mb_strtoupper($extracted_columnspec['type']),
                $extracted_columnspec['spec_in_brackets'],
                $extracted_columnspec['attribute'],
                $data['Collation'] ?? '',
                $data['Null'] === 'YES' ? 'YES' : 'NO',
                $default_type,
                $current_timestamp ? '' : $data['Default'],
                isset($data['Extra']) && $data['Extra'] !== '' ? $data['Extra']
                        : false,
                isset($data['COLUMN_COMMENT']) && $data['COLUMN_COMMENT'] !== ''
                        ? $data['COLUMN_COMMENT'] : false,
                $data['Virtuality'],
                $data['Expression'],
                $i === 0 ? '-first' : $column_names[$i - 1]
            );
            // update current column_names array, first delete old position
            for ($j = 0, $ll = count($column_names); $j < $ll; $j++) {
                if ($column_names[$j] != $column) {
                    continue;
                }

                unset($column_names[$j]);
            }

            // insert moved column
            array_splice($column_names, $i, 0, $column);
        }

        if (empty($changes) && ! isset($_REQUEST['preview_sql'])) { // should never happen
            $this->response->setRequestStatus(false);

            return;
        }

        // query for moving the columns
        $sql_query = sprintf(
            'ALTER TABLE %s %s',
            Util::backquote($this->table),
            implode(', ', $changes)
        );

        if (isset($_REQUEST['preview_sql'])) { // preview sql
            $this->response->addJSON(
                'sql_data',
                $this->template->render('preview_sql', ['query_data' => $sql_query])
            );

            return;
        }

        $this->dbi->tryQuery($sql_query);
        $tmp_error = $this->dbi->getError();
        if ($tmp_error !== '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error($tmp_error));

            return;
        }

        $message = Message::success(
            __('The columns have been moved successfully.')
        );
        $this->response->addJSON('message', $message);
        $this->response->addJSON('columns', $column_names);
    }
}
