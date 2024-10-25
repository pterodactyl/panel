<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Statement;

/**
 * `DROP` statement.
 */
class DropStatement extends Statement
{
    /**
     * Options of this statement.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        'DATABASE' => 1,
        'EVENT' => 1,
        'FUNCTION' => 1,
        'INDEX' => 1,
        'LOGFILE' => 1,
        'PROCEDURE' => 1,
        'SCHEMA' => 1,
        'SERVER' => 1,
        'TABLE' => 1,
        'VIEW' => 1,
        'TABLESPACE' => 1,
        'TRIGGER' => 1,
        'USER' => 1,

        'TEMPORARY' => 2,
        'IF EXISTS' => 3,
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
        'DROP' => [
            'DROP',
            2,
        ],
        // Used for options.
        '_OPTIONS' => [
            '_OPTIONS',
            1,
        ],
        // Used for select expressions.
        'DROP_' => [
            'DROP',
            1,
        ],
        'ON' => [
            'ON',
            3,
        ],
    ];

    /**
     * Dropped elements.
     *
     * @var Expression[]|null
     */
    public $fields;

    /**
     * Table of the dropped index.
     *
     * @var Expression|null
     */
    public $table;
}
