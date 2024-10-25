<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\FunctionCall;
use PhpMyAdmin\SqlParser\Statement;

use function implode;

/**
 * `CALL` statement.
 *
 * CALL sp_name([parameter[,...]])
 *
 * or
 *
 * CALL sp_name[()]
 */
class CallStatement extends Statement
{
    /**
     * The name of the function and its parameters.
     *
     * @var FunctionCall|null
     */
    public $call;

    /**
     * Build statement for CALL.
     *
     * @return string
     */
    public function build()
    {
        return 'CALL ' . $this->call->name . '('
            . ($this->call->parameters ? implode(',', $this->call->parameters->raw) : '') . ')';
    }
}
