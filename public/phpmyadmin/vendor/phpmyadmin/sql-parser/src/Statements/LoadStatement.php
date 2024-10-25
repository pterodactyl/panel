<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\ExpressionArray;
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
 * `LOAD` statement.
 *
 * LOAD DATA [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name'
 *   [REPLACE | IGNORE]
 *   INTO TABLE tbl_name
 *   [PARTITION (partition_name,...)]
 *   [CHARACTER SET charset_name]
 *   [{FIELDS | COLUMNS}
 *       [TERMINATED BY 'string']
 *       [[OPTIONALLY] ENCLOSED BY 'char']
 *       [ESCAPED BY 'char']
 *   ]
 *   [LINES
 *       [STARTING BY 'string']
 *       [TERMINATED BY 'string']
 *  ]
 *   [IGNORE number {LINES | ROWS}]
 *   [(col_name_or_user_var,...)]
 *   [SET col_name = expr,...]
 */
class LoadStatement extends Statement
{
    /**
     * Options for `LOAD` statements and their slot ID.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        'LOW_PRIORITY' => 1,
        'CONCURRENT' => 1,
        'LOCAL' => 2,
    ];

    /**
     * FIELDS/COLUMNS Options for `LOAD DATA...INFILE` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $FIELDS_OPTIONS = [
        'TERMINATED BY' => [
            1,
            'expr',
        ],
        'OPTIONALLY' => 2,
        'ENCLOSED BY' => [
            3,
            'expr',
        ],
        'ESCAPED BY' => [
            4,
            'expr',
        ],
    ];

    /**
     * LINES Options for `LOAD DATA...INFILE` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $LINES_OPTIONS = [
        'STARTING BY' => [
            1,
            'expr',
        ],
        'TERMINATED BY' => [
            2,
            'expr',
        ],
    ];

    /**
     * File name being used to load data.
     *
     * @var Expression|null
     */
    public $file_name;

    /**
     * Table used as destination for this statement.
     *
     * @var Expression|null
     */
    public $table;

    /**
     * Partitions used as source for this statement.
     *
     * @var ArrayObj|null
     */
    public $partition;

    /**
     * Character set used in this statement.
     *
     * @var Expression|null
     */
    public $charset_name;

    /**
     * Options for FIELDS/COLUMNS keyword.
     *
     * @see static::$FIELDS_OPTIONS
     *
     * @var OptionsArray|null
     */
    public $fields_options;

    /**
     * Whether to use `FIELDS` or `COLUMNS` while building.
     *
     * @var string|null
     */
    public $fields_keyword;

    /**
     * Options for OPTIONS keyword.
     *
     * @see static::$LINES_OPTIONS
     *
     * @var OptionsArray|null
     */
    public $lines_options;

    /**
     * Column names or user variables.
     *
     * @var Expression[]|null
     */
    public $col_name_or_user_var;

    /**
     * SET clause's updated values(optional).
     *
     * @var SetOperation[]|null
     */
    public $set;

    /**
     * Ignore 'number' LINES/ROWS.
     *
     * @var Expression|null
     */
    public $ignore_number;

    /**
     * REPLACE/IGNORE Keyword.
     *
     * @var string|null
     */
    public $replace_ignore;

    /**
     * LINES/ROWS Keyword.
     *
     * @var string|null
     */
    public $lines_rows;

    /**
     * @return string
     */
    public function build()
    {
        $ret = 'LOAD DATA ' . $this->options
            . ' INFILE ' . $this->file_name;

        if ($this->replace_ignore !== null) {
            $ret .= ' ' . trim($this->replace_ignore);
        }

        $ret .= ' INTO TABLE ' . $this->table;

        if ($this->partition !== null && strlen((string) $this->partition) > 0) {
            $ret .= ' PARTITION ' . ArrayObj::build($this->partition);
        }

        if ($this->charset_name !== null) {
            $ret .= ' CHARACTER SET ' . $this->charset_name;
        }

        if ($this->fields_keyword !== null) {
            $ret .= ' ' . $this->fields_keyword . ' ' . $this->fields_options;
        }

        if ($this->lines_options !== null && strlen((string) $this->lines_options) > 0) {
            $ret .= ' LINES ' . $this->lines_options;
        }

        if ($this->ignore_number !== null) {
            $ret .= ' IGNORE ' . $this->ignore_number . ' ' . $this->lines_rows;
        }

        if ($this->col_name_or_user_var !== null && count($this->col_name_or_user_var) > 0) {
            $ret .= ' ' . ExpressionArray::build($this->col_name_or_user_var);
        }

        if ($this->set !== null && count($this->set) > 0) {
            $ret .= ' SET ' . SetOperation::build($this->set);
        }

        return $ret;
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list)
    {
        ++$list->idx; // Skipping `LOAD DATA`.

        // parse any options if provided
        $this->options = OptionsArray::parse($parser, $list, static::$OPTIONS);
        ++$list->idx;

        /**
         * The state of the parser.
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
                if ($token->type === Token::TYPE_KEYWORD && $token->keyword !== 'INFILE') {
                    $parser->error('Unexpected keyword.', $token);
                    break;
                }

                if ($token->type !== Token::TYPE_KEYWORD) {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                ++$list->idx;
                $this->file_name = Expression::parse(
                    $parser,
                    $list,
                    ['parseField' => 'file']
                );
                $state = 1;
            } elseif ($state === 1) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    if ($token->keyword === 'REPLACE' || $token->keyword === 'IGNORE') {
                        $this->replace_ignore = trim($token->keyword);
                    } elseif ($token->keyword === 'INTO') {
                        $state = 2;
                    }
                }
            } elseif ($state === 2) {
                if ($token->type !== Token::TYPE_KEYWORD || $token->keyword !== 'TABLE') {
                    $parser->error('Unexpected token.', $token);
                    break;
                }

                ++$list->idx;
                $this->table = Expression::parse($parser, $list, ['parseField' => 'table']);
                $state = 3;
            } elseif ($state >= 3 && $state <= 7) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    $newState = $this->parseKeywordsAccordingToState($parser, $list, $state);
                    if ($newState === $state) {
                        // Avoid infinite loop
                        break;
                    }
                } elseif ($token->type === Token::TYPE_OPERATOR && $token->token === '(') {
                    $this->col_name_or_user_var
                        = ExpressionArray::parse($parser, $list);
                    $state = 7;
                } else {
                    $parser->error('Unexpected token.', $token);
                    break;
                }
            }
        }

        --$list->idx;
    }

    /**
     * @param Parser     $parser  The parser
     * @param TokensList $list    A token list
     * @param string     $keyword The keyword
     */
    public function parseFileOptions(Parser $parser, TokensList $list, $keyword = 'FIELDS'): void
    {
        ++$list->idx;

        if ($keyword === 'FIELDS' || $keyword === 'COLUMNS') {
            // parse field options
            $this->fields_options = OptionsArray::parse($parser, $list, static::$FIELDS_OPTIONS);

            $this->fields_keyword = $keyword;
        } else {
            // parse line options
            $this->lines_options = OptionsArray::parse($parser, $list, static::$LINES_OPTIONS);
        }
    }

    /**
     * @param Parser     $parser
     * @param TokensList $list
     * @param int        $state
     *
     * @return int
     */
    public function parseKeywordsAccordingToState($parser, $list, $state)
    {
        $token = $list->tokens[$list->idx];

        switch ($state) {
            case 3:
                if ($token->keyword === 'PARTITION') {
                    ++$list->idx;
                    $this->partition = ArrayObj::parse($parser, $list);

                    return 4;
                }

                // no break
            case 4:
                if ($token->keyword === 'CHARACTER SET') {
                    ++$list->idx;
                    $this->charset_name = Expression::parse($parser, $list);

                    return 5;
                }

                // no break
            case 5:
                if ($token->keyword === 'FIELDS' || $token->keyword === 'COLUMNS' || $token->keyword === 'LINES') {
                    $this->parseFileOptions($parser, $list, $token->value);

                    return 6;
                }

                // no break
            case 6:
                if ($token->keyword === 'IGNORE') {
                    ++$list->idx;

                    $this->ignore_number = Expression::parse($parser, $list);
                    $nextToken = $list->getNextOfType(Token::TYPE_KEYWORD);

                    if (
                        $nextToken->type === Token::TYPE_KEYWORD
                        && (($nextToken->keyword === 'LINES')
                        || ($nextToken->keyword === 'ROWS'))
                    ) {
                        $this->lines_rows = $nextToken->token;
                    }

                    return 7;
                }

                // no break
            case 7:
                if ($token->keyword === 'SET') {
                    ++$list->idx;
                    $this->set = SetOperation::parse($parser, $list);

                    return 8;
                }

                // no break
            default:
        }

        return $state;
    }
}
