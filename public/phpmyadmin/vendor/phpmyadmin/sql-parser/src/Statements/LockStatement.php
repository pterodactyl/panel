<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\LockExpression;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function trim;

/**
 * `LOCK` statement.
 */
class LockStatement extends Statement
{
    /**
     * Tables with their Lock expressions.
     *
     * @var LockExpression[]
     */
    public $locked = [];

    /**
     * Whether it's a LOCK statement
     * if false, it's an UNLOCK statement
     *
     * @var bool
     */
    public $isLock = true;

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list)
    {
        if ($list->tokens[$list->idx]->value === 'UNLOCK') {
            // this is in fact an UNLOCK statement
            $this->isLock = false;
        }

        ++$list->idx; // Skipping `LOCK`.

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------- [ TABLES ] -----------------> 1
         *      1 -------------- [ lock_expr ] ----------------> 2
         *      2 ------------------ [ , ] --------------------> 1
         *
         * @var int
         */
        $state = 0;

        /**
         * Previous parsed token
         */
        $prevToken = null;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    if ($token->keyword !== 'TABLES') {
                        $parser->error('Unexpected keyword.', $token);
                        break;
                    }

                    $state = 1;
                    continue;
                }

                $parser->error('Unexpected token.', $token);
                break;
            }

            if ($state === 1) {
                if (! $this->isLock) {
                    // UNLOCK statement should not have any more tokens
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                $this->locked[] = LockExpression::parse($parser, $list);
                $state = 2;
            } elseif ($state === 2) {
                if ($token->value === ',') {
                    // move over to parsing next lock expression
                    $state = 1;
                }
            }

            $prevToken = $token;
        }

        if ($state === 2 || $prevToken === null) {
            return;
        }

        $parser->error('Unexpected end of LOCK statement.', $prevToken);
    }

    /**
     * @return string
     */
    public function build()
    {
        return trim(($this->isLock ? 'LOCK' : 'UNLOCK')
            . ' TABLES ' . LockExpression::build($this->locked));
    }
}
