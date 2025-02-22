<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function array_slice;
use function count;

/**
 * `EXPLAIN` statement.
 */
class ExplainStatement extends Statement
{
    /**
     * Options for `EXPLAIN` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [

        'EXTENDED' => 1,
        'PARTITIONS' => 1,
        'FORMAT' => [
            1,
            'var',
        ],
    ];

    /**
     * The parser of the statement to be explained
     *
     * @var Parser|null
     */
    public $bodyParser = null;

    /**
     * The statement alias, could be any of the following:
     * - {EXPLAIN | DESCRIBE | DESC}
     * - {EXPLAIN | DESCRIBE | DESC} ANALYZE
     * - ANALYZE
     *
     * @var string
     */
    public $statementAlias;

    /**
     * The connection identifier, if used.
     *
     * @var int|null
     */
    public $connectionId = null;

    /**
     * The explained database for the table's name, if used.
     *
     * @var string|null
     */
    public $explainedDatabase = null;

    /**
     * The explained table's name, if used.
     *
     * @var string|null
     */
    public $explainedTable = null;

    /**
     * The explained column's name, if used.
     *
     * @var string|null
     */
    public $explainedColumn = null;

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list)
    {
        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ EXPLAIN/EXPLAIN ANALYZE/ANALYZE ]-----------------------> 1
         *
         *      0 ------------------------[ EXPLAIN/DESC/DESCRIBE ]----------------------------> 3
         *
         *      1 ------------------------------[ OPTIONS ]------------------------------------> 2
         *
         *      2 --------------[ tablename / STATEMENT / FOR CONNECTION ]---------------------> 2
         *
         *      3 -----------------------------[ tablename ]-----------------------------------> 3
         *
         * @var int
         */
        $state = 0;

        /**
         * To Differentiate between ANALYZE / EXPLAIN / EXPLAIN ANALYZE
         * 0 -> ANALYZE ( used by mariaDB https://mariadb.com/kb/en/analyze-statement)
         * 1 -> {EXPLAIN | DESCRIBE | DESC}
         * 2 -> {EXPLAIN | DESCRIBE | DESC} ANALYZE
         */
        $miniState = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                --$list->idx; // Back up one token, no real reasons to document
                break;
            }

            // Skipping whitespaces and comments.
            if ($token->type === Token::TYPE_WHITESPACE || $token->type === Token::TYPE_COMMENT) {
                continue;
            }

            if ($state === 0) {
                if ($token->keyword === 'ANALYZE' && $miniState === 0) {
                    $state = 1;
                    $this->statementAlias = 'ANALYZE';
                } elseif (
                    $token->keyword === 'EXPLAIN'
                    || $token->keyword === 'DESC'
                    || $token->keyword === 'DESCRIBE'
                ) {
                    $this->statementAlias = $token->keyword;

                    $lastIdx = $list->idx;
                    $list->idx++; // Ignore the current token
                    $nextKeyword = $list->getNextOfType(Token::TYPE_KEYWORD);
                    $list->idx = $lastIdx;

                    // There is no other keyword, we must be describing a table
                    if ($nextKeyword === null) {
                        $state = 3;
                        continue;
                    }

                    $miniState = 1;

                    $lastIdx = $list->idx;
                    $nextKeyword = $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'ANALYZE');
                    if ($nextKeyword && $nextKeyword->keyword !== null) {
                        $miniState = 2;
                        $this->statementAlias .= ' ANALYZE';
                    } else {
                        $list->idx = $lastIdx;
                    }

                    $state = 1;
                }
            } elseif ($state === 1) {
                // Parsing options.
                $this->options = OptionsArray::parse($parser, $list, static::$OPTIONS);
                $state = 2;
            } elseif ($state === 2) {
                $currIdx = $list->idx;
                $list->idx++; // Ignore the current token
                $nextToken = $list->getNext();
                $list->idx = $currIdx;

                if ($token->keyword === 'FOR' && $nextToken->keyword === 'CONNECTION') {
                    $list->idx++; // Ignore the current token
                    $list->getNext(); // CONNECTION
                    $nextToken = $list->getNext(); // Identifier
                    $this->connectionId = $nextToken->value;
                    break;
                }

                if (
                    $token->keyword !== 'SELECT'
                    && $token->keyword !== 'TABLE'
                    && $token->keyword !== 'INSERT'
                    && $token->keyword !== 'REPLACE'
                    && $token->keyword !== 'UPDATE'
                    && $token->keyword !== 'DELETE'
                ) {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                // Index of the last parsed token by default would be the last token in the $list, because we're
                // assuming that all remaining tokens at state 2, are related to the to-be-explained statement.
                $idxOfLastParsedToken = $list->count - 1;
                $subList = new TokensList(array_slice($list->tokens, $list->idx));

                $this->bodyParser = new Parser($subList);
                if (count($this->bodyParser->errors)) {
                    foreach ($this->bodyParser->errors as $error) {
                        $parser->errors[] = $error;
                    }

                    break;
                }

                $list->idx = $idxOfLastParsedToken;
                break;
            } elseif ($state === 3) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '.')) {
                    continue;
                }

                if ($this->explainedDatabase === null) {
                    $lastIdx = $list->idx;
                    $nextDot = $list->getNextOfTypeAndValue(Token::TYPE_OPERATOR, '.');
                    $list->idx = $lastIdx;
                    if ($nextDot !== null) {// We found a dot, so it must be a db.table name format
                        $this->explainedDatabase = $token->value;
                        continue;
                    }
                }

                if ($this->explainedTable === null) {
                    $this->explainedTable = $token->value;
                    continue;
                }

                if ($this->explainedColumn === null) {
                    $this->explainedColumn = $token->value;
                }
            }
        }

        if ($state !== 3 || $this->explainedTable !== null) {
            return;
        }

        // We reached end of the state 3 and no table name was found
        /** Token parsed at this moment. */
        $token = $list->tokens[$list->idx];
        $parser->error('Expected a table name.', $token);
    }

    public function build(): string
    {
        $str = $this->statementAlias;

        if ($this->options !== null) {
            if (count($this->options->options)) {
                $str .= ' ';
            }

            $str .= OptionsArray::build($this->options) . ' ';
        }

        if ($this->options === null) {
            $str .= ' ';
        }

        if ($this->bodyParser) {
            foreach ($this->bodyParser->statements as $statement) {
                $str .= $statement->build();
            }
        } elseif ($this->connectionId) {
            $str .= 'FOR CONNECTION ' . $this->connectionId;
        }

        if ($this->explainedDatabase !== null && $this->explainedTable !== null) {
            $str .= Context::escape($this->explainedDatabase) . '.' . Context::escape($this->explainedTable);
        } elseif ($this->explainedTable !== null) {
            $str .= Context::escape($this->explainedTable);
        }

        if ($this->explainedColumn !== null) {
            $str .= ' ' . Context::escape($this->explainedColumn);
        }

        return $str;
    }
}
