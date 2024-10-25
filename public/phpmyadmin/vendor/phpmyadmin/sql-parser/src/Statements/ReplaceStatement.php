<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Array2d;
use PhpMyAdmin\SqlParser\Components\IntoKeyword;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\SetOperation;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function count;
use function strlen;
use function trim;

/**
 * `REPLACE` statement.
 *
 * REPLACE [LOW_PRIORITY | DELAYED]
 *     [INTO] tbl_name [(col_name,...)]
 *     {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
 *
 * or
 *
 * REPLACE [LOW_PRIORITY | DELAYED]
 *     [INTO] tbl_name
 *     SET col_name={expr | DEFAULT}, ...
 *
 * or
 *
 * REPLACE [LOW_PRIORITY | DELAYED]
 *   [INTO] tbl_name
 *   [PARTITION (partition_name,...)]
 *   [(col_name,...)]
 *   SELECT ...
 */
class ReplaceStatement extends Statement
{
    /**
     * Options for `REPLACE` statements and their slot ID.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        'LOW_PRIORITY' => 1,
        'DELAYED' => 1,
    ];

    /**
     * Tables used as target for this statement.
     *
     * @var IntoKeyword|null
     */
    public $into;

    /**
     * Values to be replaced.
     *
     * @var Array2d|null
     */
    public $values;

    /**
     * If SET clause is present
     * holds the SetOperation.
     *
     * @var SetOperation[]|null
     */
    public $set;

    /**
     * If SELECT clause is present
     * holds the SelectStatement.
     *
     * @var SelectStatement|null
     */
    public $select;

    /**
     * @return string
     */
    public function build()
    {
        $ret = 'REPLACE ' . $this->options;
        $ret = trim($ret) . ' INTO ' . $this->into;

        if ($this->values !== null && count($this->values) > 0) {
            $ret .= ' VALUES ' . Array2d::build($this->values);
        } elseif ($this->set !== null && count($this->set) > 0) {
            $ret .= ' SET ' . SetOperation::build($this->set);
        } elseif ($this->select !== null && strlen((string) $this->select) > 0) {
            $ret .= ' ' . $this->select->build();
        }

        return $ret;
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     *
     * @return void
     */
    public function parse(Parser $parser, TokensList $list)
    {
        ++$list->idx; // Skipping `REPLACE`.

        // parse any options if provided
        $this->options = OptionsArray::parse($parser, $list, static::$OPTIONS);

        ++$list->idx;

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------------------[ INTO ]----------------------------------> 1
         *
         *      1 -------------------------[ VALUES/VALUE/SET/SELECT ]-----------------------> 2
         *
         * @var int
         */
        $state = 0;

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
                if ($token->type === Token::TYPE_KEYWORD && $token->keyword !== 'INTO') {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                ++$list->idx;
                $this->into = IntoKeyword::parse(
                    $parser,
                    $list,
                    ['fromReplace' => true]
                );

                $state = 1;
            } elseif ($state === 1) {
                if ($token->type !== Token::TYPE_KEYWORD) {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                if ($token->keyword === 'VALUE' || $token->keyword === 'VALUES') {
                    ++$list->idx; // skip VALUES

                    $this->values = Array2d::parse($parser, $list);
                } elseif ($token->keyword === 'SET') {
                    ++$list->idx; // skip SET

                    $this->set = SetOperation::parse($parser, $list);
                } elseif ($token->keyword === 'SELECT') {
                    $this->select = new SelectStatement($parser, $list);
                } else {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                $state = 2;
            }
        }

        --$list->idx;
    }
}
