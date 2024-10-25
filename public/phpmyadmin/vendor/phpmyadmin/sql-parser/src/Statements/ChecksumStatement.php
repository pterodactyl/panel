<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

/**
 * `CHECKSUM` statement.
 *
 * CHECKSUM TABLE tbl_name [, tbl_name] ... [ QUICK | EXTENDED ]
 */
class ChecksumStatement extends MaintenanceStatement
{
    /**
     * Options of this statement.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        'TABLE' => 1,

        'QUICK' => 2,
        'EXTENDED' => 3,
    ];
}
