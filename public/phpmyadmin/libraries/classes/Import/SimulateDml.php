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
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function array_reverse;
use function implode;

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
     * @param DeleteStatement|UpdateStatement $statement
     *
     * @return array<string, int|string>|null
     * @psalm-return array{
     *   sql_query: string,
     *   matched_rows: int,
     *   matched_rows_url: string
     * }
     */
    public function getMatchedRows(Parser $parser, Statement $statement): array
    {
        if ($statement instanceof DeleteStatement) {
            $matchedRowsQuery = $this->getSimulatedDeleteQuery($parser, $statement);
        } else {
            $matchedRowsQuery = $this->getSimulatedUpdateQuery($parser, $statement);
        }

        // Execute the query and get the number of matched rows.
        $matchedRows = $this->executeMatchedRowQuery($matchedRowsQuery);
        $matchedRowsUrl = Url::getFromRoute('/sql', [
            'db' => $GLOBALS['db'],
            'sql_query' => $matchedRowsQuery,
            'sql_signature' => Core::signSqlQuery($matchedRowsQuery),
        ]);

        return [
            'sql_query' => Html\Generator::formatSql($statement->build()),
            'matched_rows' => $matchedRows,
            'matched_rows_url' => $matchedRowsUrl,
        ];
    }

    /**
     * Executes the matched_row_query and returns the resultant row count.
     *
     * @param string $matchedRowQuery SQL query
     */
    private function executeMatchedRowQuery(string $matchedRowQuery): int
    {
        $this->dbi->selectDb($GLOBALS['db']);
        $result = $this->dbi->tryQuery($matchedRowQuery);
        if (! $result) {
            return 0;
        }

        return (int) $result->numRows();
    }

    /**
     * Transforms a DELETE query into SELECT statement.
     *
     * @return string SQL query
     */
    private function getSimulatedDeleteQuery(Parser $parser, DeleteStatement $statement): string
    {
        $tableReferences = Query::getTables($statement);
        Assert::count($tableReferences, 1, 'No joins allowed in simulation query');
        Assert::notNull($parser->list, 'Parser list not set');

        $condition = Query::getClause($statement, $parser->list, 'WHERE');
        $where = $condition === '' ? '' : ' WHERE ' . $condition;
        $order = $statement->order === null || $statement->order === []
            ? ''
            : ' ORDER BY ' . Query::getClause($statement, $parser->list, 'ORDER BY');
        $limit = $statement->limit === null ? '' : ' LIMIT ' . Query::getClause($statement, $parser->list, 'LIMIT');

        return 'SELECT * FROM ' . $tableReferences[0] . $where . $order . $limit;
    }

    /**
     * Transforms a UPDATE query into SELECT statement.
     *
     * @return string SQL query
     */
    private function getSimulatedUpdateQuery(Parser $parser, UpdateStatement $statement): string
    {
        $tableReferences = Query::getTables($statement);
        Assert::count($tableReferences, 1, 'No joins allowed in simulation query');
        Assert::isNonEmptyList($statement->set, 'SET statements missing');
        Assert::notNull($parser->list, 'Parser list not set');

        $values = [];
        $newColumns = [];
        $oldColumns = [];
        foreach (array_reverse($statement->set) as $set) {
            $column = Util::unQuote($set->column);
            if (array_key_exists($column, $values)) {
                continue;
            }

            $oldColumns[] = Util::backquote($column);
            $values[$column] = $set->value . ' AS ' . ($newColumns[] = Util::backquote($column . ' `new`'));
        }

        $condition = Query::getClause($statement, $parser->list, 'WHERE');
        $where = $condition === '' ? '' : ' WHERE ' . $condition;
        $order = $statement->order === null || $statement->order === []
            ? ''
            : ' ORDER BY ' . Query::getClause($statement, $parser->list, 'ORDER BY');
        $limit = $statement->limit === null ? '' : ' LIMIT ' . Query::getClause($statement, $parser->list, 'LIMIT');

        return 'SELECT *' .
            ' FROM (' .
            'SELECT *, ' . implode(', ', $values) . ' FROM ' . $tableReferences[0] . $where . $order . $limit .
            ') AS `pma_tmp`' .
            ' WHERE NOT (' . implode(', ', $oldColumns) . ') <=> (' . implode(', ', $newColumns) . ')';
    }
}
