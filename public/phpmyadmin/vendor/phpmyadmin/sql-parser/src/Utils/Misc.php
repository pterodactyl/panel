<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;

/**
 * Miscellaneous utilities.
 */
class Misc
{
    /**
     * Gets a list of all aliases and their original names.
     *
     * @param SelectStatement $statement the statement to be processed
     * @param string          $database  the name of the database
     *
     * @return array<string, array<string, array<string, array<string, array<string, string>|string|null>>|null>>
     */
    public static function getAliases($statement, $database)
    {
        if (! ($statement instanceof SelectStatement) || empty($statement->expr) || empty($statement->from)) {
            return [];
        }

        $retval = [];

        $tables = [];

        /**
         * Expressions that may contain aliases.
         * These are extracted from `FROM` and `JOIN` keywords.
         *
         * @var Expression[]
         */
        $expressions = $statement->from;

        // Adding expressions from JOIN.
        if (! empty($statement->join)) {
            foreach ($statement->join as $join) {
                $expressions[] = $join->expr;
            }
        }

        foreach ($expressions as $expr) {
            if (! isset($expr->table) || ($expr->table === '')) {
                continue;
            }

            $thisDb = isset($expr->database) && ($expr->database !== '') ?
                $expr->database : $database;

            if (! isset($retval[$thisDb])) {
                $retval[$thisDb] = [
                    'alias' => null,
                    'tables' => [],
                ];
            }

            if (! isset($retval[$thisDb]['tables'][$expr->table])) {
                $retval[$thisDb]['tables'][$expr->table] = [
                    'alias' => isset($expr->alias) && ($expr->alias !== '') ?
                        $expr->alias : null,
                    'columns' => [],
                ];
            }

            if (! isset($tables[$thisDb])) {
                $tables[$thisDb] = [];
            }

            $tables[$thisDb][$expr->alias] = $expr->table;
        }

        foreach ($statement->expr as $expr) {
            if (! isset($expr->column, $expr->alias) || ($expr->column === '') || ($expr->alias === '')) {
                continue;
            }

            $thisDb = isset($expr->database) && ($expr->database !== '') ?
                $expr->database : $database;

            if (isset($expr->table) && ($expr->table !== '')) {
                $thisTable = $tables[$thisDb][$expr->table] ?? $expr->table;
                $retval[$thisDb]['tables'][$thisTable]['columns'][$expr->column] = $expr->alias;
            } else {
                foreach ($retval[$thisDb]['tables'] as &$table) {
                    $table['columns'][$expr->column] = $expr->alias;
                }
            }
        }

        return $retval;
    }
}
