<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Url;

use function implode;
use function strtoupper;

final class SimulateDml
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    public function getError(): string
    {
        return $this->dbi->getError();
    }

    /**
     * Find the matching rows for UPDATE/DELETE query.
     *
     * @param DeleteStatement|UpdateStatement|Statement $statement
     *
     * @return array<string, int|string>
     * @psalm-return array{
     *   sql_query: string,
     *   matched_rows: (int|numeric-string),
     *   matched_rows_url: string
     * }
     */
    public function getMatchedRows(string $query, Parser $parser, $statement): array
    {
        $matchedRowQuery = '';
        if ($statement instanceof DeleteStatement) {
            $matchedRowQuery = $this->getSimulatedDeleteQuery($parser, $statement);
        } elseif ($statement instanceof UpdateStatement) {
            $matchedRowQuery = $this->getSimulatedUpdateQuery($parser, $statement);
        }

        // Execute the query and get the number of matched rows.
        $matchedRows = $this->executeMatchedRowQuery($matchedRowQuery);
        $matchedRowsUrl = Url::getFromRoute('/sql', [
            'db' => $GLOBALS['db'],
            'sql_query' => $matchedRowQuery,
            'sql_signature' => Core::signSqlQuery($matchedRowQuery),
        ]);

        return [
            'sql_query' => Html\Generator::formatSql($query),
            'matched_rows' => $matchedRows,
            'matched_rows_url' => $matchedRowsUrl,
        ];
    }

    /**
     * Executes the matched_row_query and returns the resultant row count.
     *
     * @param string $matchedRowQuery SQL query
     *
     * @return int|string
     * @psalm-return int|numeric-string
     */
    private function executeMatchedRowQuery(string $matchedRowQuery)
    {
        $this->dbi->selectDb($GLOBALS['db']);
        // Execute the query.
        $result = $this->dbi->tryQuery($matchedRowQuery);
        if (! $result) {
            return 0;
        }

        // Count the number of rows in the result set.
        return $result->numRows();
    }

    /**
     * Transforms a DELETE query into SELECT statement.
     *
     * @return string SQL query
     */
    private function getSimulatedDeleteQuery(Parser $parser, DeleteStatement $statement): string
    {
        $tableReferences = Query::getTables($statement);
        $where = Query::getClause($statement, $parser->list, 'WHERE');
        if (empty($where)) {
            $where = '1';
        }

        $orderAndLimit = '';
        if (! empty($statement->order)) {
            $orderAndLimit .= ' ORDER BY ' . Query::getClause($statement, $parser->list, 'ORDER BY');
        }

        if (! empty($statement->limit)) {
            $orderAndLimit .= ' LIMIT ' . Query::getClause($statement, $parser->list, 'LIMIT');
        }

        return 'SELECT * FROM ' . implode(', ', $tableReferences) .
            ' WHERE ' . $where . $orderAndLimit;
    }

    /**
     * Transforms a UPDATE query into SELECT statement.
     *
     * @return string SQL query
     */
    private function getSimulatedUpdateQuery(Parser $parser, UpdateStatement $statement): string
    {
        $tableReferences = Query::getTables($statement);
        $where = Query::getClause($statement, $parser->list, 'WHERE');
        if (empty($where)) {
            $where = '1';
        }

        $columns = [];
        $diff = [];
        foreach ($statement->set as $set) {
            $columns[] = $set->column;
            $notEqualOperator = ' <> ';
            if (strtoupper($set->value) === 'NULL') {
                $notEqualOperator = ' IS NOT ';
            }

            $diff[] = $set->column . $notEqualOperator . $set->value;
        }

        if (! empty($diff)) {
            $where .= ' AND (' . implode(' OR ', $diff) . ')';
        }

        $orderAndLimit = '';
        if (! empty($statement->order)) {
            $orderAndLimit .= ' ORDER BY ' . Query::getClause($statement, $parser->list, 'ORDER BY');
        }

        if (! empty($statement->limit)) {
            $orderAndLimit .= ' LIMIT ' . Query::getClause($statement, $parser->list, 'LIMIT');
        }

        return 'SELECT ' . implode(', ', $columns) .
            ' FROM ' . implode(', ', $tableReferences) .
            ' WHERE ' . $where . $orderAndLimit;
    }
}
