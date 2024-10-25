<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Statement;

/**
 * `ANALYZE` statement.
 *
 * ANALYZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE
 *  tbl_name [, tbl_name] ...
 */
class AnalyzeStatement extends Statement
{
    /**
     * Options of this statement.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        'TABLE' => 1,

        'NO_WRITE_TO_BINLOG' => 2,
        'LOCAL' => 3,
    ];

    /**
     * Analyzed tables.
     *
     * @var Expression[]|null
     */
    public $tables;
}
