<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * Transaction statement.
 */
class TransactionStatement extends Statement
{
    /**
     * START TRANSACTION and BEGIN.
     */
    public const TYPE_BEGIN = 1;

    /**
     * COMMIT and ROLLBACK.
     */
    public const TYPE_END = 2;

    /**
     * The type of this query.
     *
     * @var int|null
     */
    public $type;

    /**
     * The list of statements in this transaction.
     *
     * @var Statement[]|null
     */
    public $statements;

    /**
     * The ending transaction statement which may be a `COMMIT` or a `ROLLBACK`.
     *
     * @var TransactionStatement|null
     */
    public $end;

    /**
     * Options for this query.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        'START TRANSACTION' => 1,
        'BEGIN' => 1,
        'COMMIT' => 1,
        'ROLLBACK' => 1,
        'WITH CONSISTENT SNAPSHOT' => 2,
        'WORK' => 2,
        'AND NO CHAIN' => 3,
        'AND CHAIN' => 3,
        'RELEASE' => 4,
        'NO RELEASE' => 4,
    ];

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     *
     * @return void
     */
    public function parse(Parser $parser, TokensList $list)
    {
        parent::parse($parser, $list);

        // Checks the type of this query.
        if ($this->options->has('START TRANSACTION') || $this->options->has('BEGIN')) {
            $this->type = self::TYPE_BEGIN;
        } elseif ($this->options->has('COMMIT') || $this->options->has('ROLLBACK')) {
            $this->type = self::TYPE_END;
        }
    }

    /**
     * @return string
     */
    public function build()
    {
        $ret = OptionsArray::build($this->options);
        if ($this->type === self::TYPE_BEGIN) {
            foreach ($this->statements as $statement) {
                /*
                 * @var SelectStatement $statement
                 */
                $ret .= ';' . $statement->build();
            }

            $ret .= ';';
            if ($this->end !== null) {
                $ret .= $this->end->build();
            }
        }

        return $ret;
    }
}
