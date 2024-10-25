<?php
/**
 * Central Columns view/edit
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;
use function is_bool;
use function is_numeric;
use function parse_str;
use function sprintf;

class CentralColumnsController extends AbstractController
{
    /** @var CentralColumns */
    private $centralColumns;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        CentralColumns $centralColumns
    ) {
        parent::__construct($response, $template, $db);
        $this->centralColumns = $centralColumns;
    }

    public function __invoke(): void
    {
        global $cfg, $db, $message, $pos, $num_cols;

        if (isset($_POST['edit_save'])) {
            echo $this->editSave([
                'col_name' => $_POST['col_name'] ?? null,
                'orig_col_name' => $_POST['orig_col_name'] ?? null,
                'col_default' => $_POST['col_default'] ?? null,
                'col_default_sel' => $_POST['col_default_sel'] ?? null,
                'col_extra' => $_POST['col_extra'] ?? null,
                'col_isNull' => $_POST['col_isNull'] ?? null,
                'col_length' => $_POST['col_length'] ?? null,
                'col_attribute' => $_POST['col_attribute'] ?? null,
                'col_type' => $_POST['col_type'] ?? null,
                'collation' => $_POST['collation'] ?? null,
            ]);

            return;
        }

        if (isset($_POST['add_new_column'])) {
            $tmp_msg = $this->addNewColumn([
                'col_name' => $_POST['col_name'] ?? null,
                'col_default' => $_POST['col_default'] ?? null,
                'col_default_sel' => $_POST['col_default_sel'] ?? null,
                'col_extra' => $_POST['col_extra'] ?? null,
                'col_isNull' => $_POST['col_isNull'] ?? null,
                'col_length' => $_POST['col_length'] ?? null,
                'col_attribute' => $_POST['col_attribute'] ?? null,
                'col_type' => $_POST['col_type'] ?? null,
                'collation' => $_POST['collation'] ?? null,
            ]);
        }

        if (isset($_POST['getColumnList'])) {
            $this->response->addJSON('message', $this->getColumnList([
                'cur_table' => $_POST['cur_table'] ?? null,
            ]));

            return;
        }

        if (isset($_POST['add_column'])) {
            $tmp_msg = $this->addColumn([
                'table-select' => $_POST['table-select'] ?? null,
                'column-select' => $_POST['column-select'] ?? null,
            ]);
        }

        $this->addScriptFiles([
            'vendor/jquery/jquery.uitablefilter.js',
            'vendor/jquery/jquery.tablesorter.js',
            'database/central_columns.js',
        ]);

        if (isset($_POST['edit_central_columns_page'])) {
            $this->editPage([
                'selected_fld' => $_POST['selected_fld'] ?? null,
                'db' => $_POST['db'] ?? null,
            ]);

            return;
        }

        if (isset($_POST['multi_edit_central_column_save'])) {
            $message = $this->updateMultipleColumn([
                'db' => $_POST['db'] ?? null,
                'orig_col_name' => $_POST['orig_col_name'] ?? null,
                'field_name' => $_POST['field_name'] ?? null,
                'field_default_type' => $_POST['field_default_type'] ?? null,
                'field_default_value' => $_POST['field_default_value'] ?? null,
                'field_length' => $_POST['field_length'] ?? null,
                'field_attribute' => $_POST['field_attribute'] ?? null,
                'field_type' => $_POST['field_type'] ?? null,
                'field_collation' => $_POST['field_collation'] ?? null,
                'field_null' => $_POST['field_null'] ?? null,
                'col_extra' => $_POST['col_extra'] ?? null,
            ]);
            if (! is_bool($message)) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);
            }
        }

        if (isset($_POST['delete_save'])) {
            $tmp_msg = $this->deleteSave([
                'db' => $_POST['db'] ?? null,
                'col_name' => $_POST['col_name'] ?? null,
            ]);
        }

        $this->main([
            'pos' => $_POST['pos'] ?? null,
            'total_rows' => $_POST['total_rows'] ?? null,
        ]);

        $pos = 0;
        if (isset($_POST['pos']) && is_numeric($_POST['pos'])) {
            $pos = (int) $_POST['pos'];
        }

        $num_cols = $this->centralColumns->getColumnsCount($db, $pos, (int) $cfg['MaxRows']);
        $message = Message::success(
            sprintf(__('Showing rows %1$s - %2$s.'), $pos + 1, $pos + $num_cols)
        );
        if (! isset($tmp_msg) || $tmp_msg === true) {
            return;
        }

        $message = $tmp_msg;
    }

    /**
     * @param array $params Request parameters
     */
    public function main(array $params): void
    {
        global $text_dir;

        if (! empty($params['total_rows']) && is_numeric($params['total_rows'])) {
            $totalRows = (int) $params['total_rows'];
        } else {
            $totalRows = $this->centralColumns->getCount($this->db);
        }

        $pos = 0;
        if (isset($params['pos']) && is_numeric($params['pos'])) {
            $pos = (int) $params['pos'];
        }

        $variables = $this->centralColumns->getTemplateVariablesForMain($this->db, $totalRows, $pos, $text_dir);

        $this->render('database/central_columns/main', $variables);
    }

    /**
     * @param array $params Request parameters
     *
     * @return array JSON
     */
    public function getColumnList(array $params): array
    {
        return $this->centralColumns->getListRaw($this->db, $params['cur_table'] ?? '');
    }

    /**
     * @param array $params Request parameters
     *
     * @return true|Message
     */
    public function editSave(array $params)
    {
        $columnDefault = $params['col_default'];
        if ($columnDefault === 'NONE' && $params['col_default_sel'] !== 'USER_DEFINED') {
            $columnDefault = '';
        }

        return $this->centralColumns->updateOneColumn(
            $this->db,
            $params['orig_col_name'],
            $params['col_name'],
            $params['col_type'],
            $params['col_attribute'],
            $params['col_length'],
            isset($params['col_isNull']) ? 1 : 0,
            $params['collation'],
            $params['col_extra'] ?? '',
            $columnDefault
        );
    }

    /**
     * @param array $params Request parameters
     *
     * @return true|Message
     */
    public function addNewColumn(array $params)
    {
        $columnDefault = $params['col_default'];
        if ($columnDefault === 'NONE' && $params['col_default_sel'] !== 'USER_DEFINED') {
            $columnDefault = '';
        }

        return $this->centralColumns->updateOneColumn(
            $this->db,
            '',
            $params['col_name'],
            $params['col_type'],
            $params['col_attribute'],
            $params['col_length'],
            isset($params['col_isNull']) ? 1 : 0,
            $params['collation'],
            $params['col_extra'] ?? '',
            $columnDefault
        );
    }

    /**
     * @param array $params Request parameters
     *
     * @return true|Message
     */
    public function addColumn(array $params)
    {
        return $this->centralColumns->syncUniqueColumns(
            [$params['column-select']],
            false,
            $params['table-select']
        );
    }

    /**
     * @param array $params Request parameters
     */
    public function editPage(array $params): void
    {
        $rows = $this->centralColumns->getHtmlForEditingPage($params['selected_fld'], $params['db']);

        $this->render('database/central_columns/edit', ['rows' => $rows]);
    }

    /**
     * @param array $params Request parameters
     *
     * @return true|Message
     */
    public function updateMultipleColumn(array $params)
    {
        return $this->centralColumns->updateMultipleColumn($params);
    }

    /**
     * @param array $params Request parameters
     *
     * @return true|Message
     */
    public function deleteSave(array $params)
    {
        $name = [];
        parse_str($params['col_name'], $name);

        return $this->centralColumns->deleteColumnsFromList($params['db'], $name['selected_fld'], false);
    }
}
