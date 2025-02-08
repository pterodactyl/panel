<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\JoinKeyword;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Components\SetOperation;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

/**
 * `UPDATE` statement.
 *
 * UPDATE [LOW_PRIORITY] [IGNORE] table_reference
 *     [INNER JOIN | LEFT JOIN | JOIN] T1 ON T1.C1 = T2.C1
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
        'JOIN' => [
            'JOIN',
            1,
        ],
        'LEFT JOIN' => [
            'LEFT JOIN',
            1,
        ],
        'INNER JOIN' => [
            'INNER JOIN',
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

    /**
     * Joins.
     *
     * @var JoinKeyword[]|null
     */
    public $join;

    /**
     * Function called after the token was processed.
     * In the update statement, this is used to check that at least one assignment has been set to throw an error if a
     * query like `UPDATE acme SET WHERE 1;` is parsed.
     *
     * @return void
     *
     * @throws ParserException throws the exception, if strict mode is enabled.
     */
    public function after(Parser $parser, TokensList $list, Token $token)
    {
        /** @psalm-var string $tokenValue */
        $tokenValue = $token->value;
        // Ensure we finished to parse the "SET" token, and if yes, ensure that assignments are defined.
        if ($this->set !== [] || (Parser::$KEYWORD_PARSERS[$tokenValue]['field'] ?? null) !== 'set') {
            return;
        }

        $parser->error('Missing assignment in SET operation.', $list->tokens[$list->idx]);
    }
}
