<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\ExpressionArray;
use PhpMyAdmin\SqlParser\Components\JoinKeyword;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function count;
use function stripos;
use function strlen;

/**
 * `DELETE` statement.
 *
 * DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
 *     [PARTITION (partition_name,...)]
 *     [WHERE where_condition]
 *     [ORDER BY ...]
 *     [LIMIT row_count]
 *
 * Multi-table syntax
 *
 * DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
 *   tbl_name[.*] [, tbl_name[.*]] ...
 *   FROM table_references
 *   [WHERE where_condition]
 *
 * OR
 *
 * DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
 *   FROM tbl_name[.*] [, tbl_name[.*]] ...
 *   USING table_references
 *   [WHERE where_condition]
 */
class DeleteStatement extends Statement
{
    /**
     * Options for `DELETE` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        'LOW_PRIORITY' => 1,
        'QUICK' => 2,
        'IGNORE' => 3,
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
        'DELETE' => [
            'DELETE',
            2,
        ],
        // Used for options.
        '_OPTIONS' => [
            '_OPTIONS',
            1,
        ],
        'FROM' => [
            'FROM',
            3,
        ],
        'PARTITION' => [
            'PARTITION',
            3,
        ],
        'USING' => [
            'USING',
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
     * Table(s) used as sources for this statement.
     *
     * @var Expression[]|null
     */
    public $from;

    /**
     * Joins.
     *
     * @var JoinKeyword[]|null
     */
    public $join;

    /**
     * Tables used as sources for this statement.
     *
     * @var Expression[]|null
     */
    public $using;

    /**
     * Columns used in this statement.
     *
     * @var Expression[]|null
     */
    public $columns;

    /**
     * Partitions used as source for this statement.
     *
     * @var ArrayObj|null
     */
    public $partition;

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
     * @return string
     */
    public function build()
    {
        $ret = 'DELETE ' . OptionsArray::build($this->options);

        if ($this->columns !== null && count($this->columns) > 0) {
            $ret .= ' ' . ExpressionArray::build($this->columns);
        }

        if ($this->from !== null && count($this->from) > 0) {
            $ret .= ' FROM ' . ExpressionArray::build($this->from);
        }

        if ($this->join !== null && count($this->join) > 0) {
            $ret .= ' ' . JoinKeyword::build($this->join);
        }

        if ($this->using !== null && count($this->using) > 0) {
            $ret .= ' USING ' . ExpressionArray::build($this->using);
        }

        if ($this->where !== null && count($this->where) > 0) {
            $ret .= ' WHERE ' . Condition::build($this->where);
        }

        if ($this->order !== null && count($this->order) > 0) {
            $ret .= ' ORDER BY ' . ExpressionArray::build($this->order);
        }

        if ($this->limit !== null && strlen((string) $this->limit) > 0) {
            $ret .= ' LIMIT ' . Limit::build($this->limit);
        }

        return $ret;
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list)
    {
        ++$list->idx; // Skipping `DELETE`.

        // parse any options if provided
        $this->options = OptionsArray::parse($parser, $list, static::$OPTIONS);
        ++$list->idx;

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------------------[ FROM ]----------------------------------> 2
         *      0 ------------------------------[ table[.*] ]--------------------------------> 1
         *      1 ---------------------------------[ FROM ]----------------------------------> 2
         *      2 --------------------------------[ USING ]----------------------------------> 3
         *      2 --------------------------------[ WHERE ]----------------------------------> 4
         *      2 --------------------------------[ ORDER ]----------------------------------> 5
         *      2 --------------------------------[ LIMIT ]----------------------------------> 6
         *
         * @var int
         */
        $state = 0;

        /**
         * If the query is multi-table or not.
         *
         * @var bool
         */
        $multiTable = false;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            if ($state === 0) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    if ($token->keyword !== 'FROM') {
                        $parser->error('Unexpected keyword.', $token);
                        break;
                    }

                    ++$list->idx; // Skip 'FROM'
                    $this->from = ExpressionArray::parse($parser, $list);

                    $state = 2;
                } else {
                    $this->columns = ExpressionArray::parse($parser, $list);
                    $state = 1;
                }
            } elseif ($state === 1) {
                if ($token->type !== Token::TYPE_KEYWORD) {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                if ($token->keyword !== 'FROM') {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                ++$list->idx; // Skip 'FROM'
                $this->from = ExpressionArray::parse($parser, $list);

                $state = 2;
            } elseif ($state === 2) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    if (stripos($token->keyword, 'JOIN') !== false) {
                        ++$list->idx;
                        $this->join = JoinKeyword::parse($parser, $list);

                        // remain in state = 2
                    } else {
                        switch ($token->keyword) {
                            case 'USING':
                                ++$list->idx; // Skip 'USING'
                                $this->using = ExpressionArray::parse($parser, $list);
                                $state = 3;

                                $multiTable = true;
                                break;
                            case 'WHERE':
                                ++$list->idx; // Skip 'WHERE'
                                $this->where = Condition::parse($parser, $list);
                                $state = 4;
                                break;
                            case 'ORDER BY':
                                ++$list->idx; // Skip 'ORDER BY'
                                $this->order = OrderKeyword::parse($parser, $list);
                                $state = 5;
                                break;
                            case 'LIMIT':
                                ++$list->idx; // Skip 'LIMIT'
                                $this->limit = Limit::parse($parser, $list);
                                $state = 6;
                                break;
                            default:
                                $parser->error('Unexpected keyword.', $token);
                                break 2;
                        }
                    }
                }
            } elseif ($state === 3) {
                if ($token->type !== Token::TYPE_KEYWORD) {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                if ($token->keyword !== 'WHERE') {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                ++$list->idx; // Skip 'WHERE'
                $this->where = Condition::parse($parser, $list);
                $state = 4;
            } elseif ($state === 4) {
                if ($multiTable === true && $token->type === Token::TYPE_KEYWORD) {
                    $parser->error('This type of clause is not valid in Multi-table queries.', $token);
                    break;
                }

                if ($token->type === Token::TYPE_KEYWORD) {
                    switch ($token->keyword) {
                        case 'ORDER BY':
                            ++$list->idx; // Skip 'ORDER  BY'
                            $this->order = OrderKeyword::parse($parser, $list);
                            $state = 5;
                            break;
                        case 'LIMIT':
                            ++$list->idx; // Skip 'LIMIT'
                            $this->limit = Limit::parse($parser, $list);
                            $state = 6;
                            break;
                        default:
                            $parser->error('Unexpected keyword.', $token);
                            break 2;
                    }
                }
            } elseif ($state === 5) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    if ($token->keyword !== 'LIMIT') {
                        $parser->error('Unexpected keyword.', $token);
                        break;
                    }

                    ++$list->idx; // Skip 'LIMIT'
                    $this->limit = Limit::parse($parser, $list);
                    $state = 6;
                }
            }
        }

        if ($state >= 2) {
            foreach ($this->from as $fromExpr) {
                $fromExpr->database = $fromExpr->table;
                $fromExpr->table = $fromExpr->column;
                $fromExpr->column = null;
            }
        }

        --$list->idx;
    }
}
