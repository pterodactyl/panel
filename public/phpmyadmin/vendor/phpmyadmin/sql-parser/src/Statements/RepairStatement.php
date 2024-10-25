<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

/**
 * `REPAIR` statement.
 *
 * REPAIR [NO_WRITE_TO_BINLOG | LOCAL] TABLE
 *  tbl_name [, tbl_name] ...
 *  [QUICK] [EXTENDED] [USE_FRM]
 */
class RepairStatement extends MaintenanceStatement
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

        'QUICK' => 4,
        'EXTENDED' => 5,
        'USE_FRM' => 6,
    ];
}
