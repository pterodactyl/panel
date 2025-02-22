<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function array_diff;
use function array_keys;
use function array_search;
use function array_splice;
use function assert;
use function count;
use function implode;
use function is_array;

final class MoveColumnsController extends AbstractController
{
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
    }

    public function __invoke(): void
    {
        $moveColumns = $_POST['move_columns'] ?? null;
        $previewSql = $_REQUEST['preview_sql'] ?? null;
        if (! is_array($moveColumns) || ! $this->response->isAjax()) {
            $this->response->setRequestStatus(false);

            return;
        }

        $this->dbi->selectDb($this->db);
        $createTableSql = $this->dbi->getTable($this->db, $this->table)->showCreate();
        $sql_query = $this->generateAlterTableSql($createTableSql, $moveColumns);

        if ($sql_query === null) {
            $this->response->setRequestStatus(false);

            return;
        }

        if ($previewSql) {
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
        $this->response->addJSON('columns', $moveColumns);
    }

    /**
     * @param array<int,mixed> $moveColumns
     * @psalm-param list<mixed> $moveColumns
     */
    private function generateAlterTableSql(string $createTableSql, array $moveColumns): ?string
    {
        $parser = new Parser($createTableSql);
        /** @var CreateStatement $statement */
        $statement = $parser->statements[0];
        /** @var CreateDefinition[] $fields */
        $fields = $statement->fields;
        $columns = [];
        foreach ($fields as $field) {
            if ($field->name === null) {
                continue;
            }

            $columns[$field->name] = $field;
        }

        $columnNames = array_keys($columns);
        // Ensure the columns from client match the columns from the table
        if (
            count($columnNames) !== count($moveColumns) ||
            array_diff($columnNames, $moveColumns) !== []
        ) {
            return null;
        }

        $changes = [];

        // move columns from first to last
        /** @psalm-var list<string> $moveColumns */
        foreach ($moveColumns as $i => $columnName) {
            // is this column already correctly placed?
            if ($columnNames[$i] == $columnName) {
                continue;
            }

            $changes[] =
                'CHANGE ' . Util::backquote($columnName) . ' ' . CreateDefinition::build($columns[$columnName]) .
                ($i === 0 ? ' FIRST' : ' AFTER ' . Util::backquote($columnNames[$i - 1]));

            // Move column to its new position
            /** @var int $j */
            $j = array_search($columnName, $columnNames, true);
            array_splice($columnNames, $j, 1);
            array_splice($columnNames, $i, 0, $columnName);
        }

        if ($changes === []) {
            return null;
        }

        assert($statement->name !== null, 'Alter table statement has no name');

        return 'ALTER TABLE ' . Util::backquote($statement->name->table) . "\n  " . implode(",\n  ", $changes);
    }
}
