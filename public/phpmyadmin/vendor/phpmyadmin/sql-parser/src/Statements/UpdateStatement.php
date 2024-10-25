<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Components\SetOperation;
use PhpMyAdmin\SqlParser\Statement;

/**
 * `UPDATE` statement.
 *
 * UPDATE [LOW_PRIORITY] [IGNORE] table_reference
 *     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
 *     [WHERE where_condition]
 *     [ORDER BY ...]
 *     [LIMIT row_count]
 *
 * or
 *
 * UPDATE [LOW_PRIORITY] [IGNORE] table_references
 *     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
 *     [WHERE where_condition]
 */
class UpdateStatement extends Statement
{
    /**
     * Options for `UPDATE` statements and their slot ID.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        'LOW_PRIORITY' => 1,
        'IGNORE' => 2,
    ];

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$CLAUSES
     *
     * @var array<string, array<int, int|string>>
     * @psalm-var array<string, array{non-empty-string, (1|2|3)}>
     */
    public static $CLAUSES = [
        'UPDATE' => [
            'UPDATE',
            2,
        ],
        // Used for options.
        '_OPTIONS' => [
            '_OPTIONS',
            1,
        ],
        // Used for updated tables.
        '_UPDATE' => [
            'UPDATE',
            1,
        ],
        'SET' => [
            'SET',
            3,
        ],
        'WHERE' => [
            'WHERE',
            3,
        ],
        'ORDER BY' => [
            'ORDER BY',
            3,
        ],
        'LIMIT' => [
            'LIMIT',
            3,
        ],
    ];

    /**
     * Tables used as sources for this statement.
     *
     * @var Expression[]|null
     */
    public $tables;

    /**
     * The updated values.
     *
     * @var SetOperation[]|null
     */
    public $set;

    /**
     * Conditions used for filtering each row of the result set.
     *
     * @var Condition[]|null
     */
    public $where;

    /**
     * Specifies the order of the rows in the result set.
     *
     * @var OrderKeyword[]|null
     */
    public $order;

    /**
     * Conditions used for limiting the size of the result set.
     *
     * @var Limit|null
     */
    public $limit;
}
