<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;
use function is_array;
use function rtrim;
use function strlen;
use function strtoupper;
use function trim;

/**
 * Parses a reference to an expression (column, table or database name, function
 * call, mathematical expression, etc.).
 *
 * @final
 */
#[\AllowDynamicProperties]
class Expression extends Component
{
    /**
     * List of allowed reserved keywords in expressions.
     *
     * @var array<string, int>
     */
    private static $ALLOWED_KEYWORDS = [
        'AND' => 1,
        'AS' => 1,
        'BETWEEN' => 1,
        'CASE' => 1,
        'DUAL' => 1,
        'DIV' => 1,
        'IS' => 1,
        'MOD' => 1,
        'NOT' => 1,
        'NOT NULL' => 1,
        'NULL' => 1,
        'OR' => 1,
        'OVER' => 1,
        'REGEXP' => 1,
        'RLIKE' => 1,
        'XOR' => 1,
    ];

    /**
     * The name of this database.
     *
     * @var string|null
     */
    public $database;

    /**
     * The name of this table.
     *
     * @var string|null
     */
    public $table;

    /**
     * The name of the column.
     *
     * @var string|null
     */
    public $column;

    /**
     * The sub-expression.
     *
     * @var string|null
     */
    public $expr = '';

    /**
     * The alias of this expression.
     *
     * @var string|null
     */
    public $alias;

    /**
     * The name of the function.
     *
     * @var mixed
     */
    public $function;

    /**
     * The type of subquery.
     *
     * @var string|null
     */
    public $subquery;

    /**
     * Syntax:
     *     new Expression('expr')
     *     new Expression('expr', 'alias')
     *     new Expression('database', 'table', 'column')
     *     new Expression('database', 'table', 'column', 'alias')
     *
     * If the database, table or column name is not required, pass an empty
     * string.
     *
     * @param string|null $database The name of the database or the expression.
     * @param string|null $table    The name of the table or the alias of the expression.
     * @param string|null $column   the name of the column
     * @param string|null $alias    the name of the alias
     */
    public function __construct($database = null, $table = null, $column = null, $alias = null)
    {
        if (($column === null) && ($alias === null)) {
            $this->expr = $database; // case 1
            $this->alias = $table; // case 2
        } else {
            $this->database = $database; // case 3
            $this->table = $table; // case 3
            $this->column = $column; // case 3
            $this->alias = $alias; // case 4
        }
    }

    /**
     * Possible options:.
     *
     *      `field`
     *
     *          First field to be filled.
     *          If this is not specified, it takes the value of `parseField`.
     *
     *      `parseField`
     *
     *          Specifies the type of the field parsed. It may be `database`,
     *          `table` or `column`. These expressions may not include
     *          parentheses.
     *
     *      `breakOnAlias`
     *
     *          If not empty, breaks when the alias occurs (it is not included).
     *
     *      `breakOnParentheses`
     *
     *          If not empty, breaks when the first parentheses occurs.
     *
     *      `parenthesesDelimited`
     *
     *          If not empty, breaks after last parentheses occurred.
     *
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return Expression|null
     *
     * @throws ParserException
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = new static();

        /**
         * Whether current tokens make an expression or a table reference.
         *
         * @var bool
         */
        $isExpr = false;

        /**
         * Whether a period was previously found.
         *
         * @var bool
         */
        $dot = false;

        /**
         * Whether an alias is expected. Is 2 if `AS` keyword was found.
         *
         * @var bool
         */
        $alias = false;

        /**
         * Counts brackets.
         *
         * @var int
         */
        $brackets = 0;

        /**
         * Keeps track of the last two previous tokens.
         *
         * @var Token[]
         */
        $prev = [
            null,
            null,
        ];

        // When a field is parsed, no parentheses are expected.
        if (! empty($options['parseField'])) {
            $options['breakOnParentheses'] = true;
            $options['field'] = $options['parseField'];
        }

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
                // If the token is a closing C comment from a MySQL command, it must be ignored.
                if ($isExpr && $token->token !== '*/') {
                    $ret->expr .= $token->token;
                }

                continue;
            }

            if ($token->type === Token::TYPE_KEYWORD) {
                if (($brackets > 0) && empty($ret->subquery) && ! empty(Parser::$STATEMENT_PARSERS[$token->keyword])) {
                    // A `(` was previously found and this keyword is the
                    // beginning of a statement, so this is a subquery.
                    $ret->subquery = $token->keyword;
                } elseif (
                    ($token->flags & Token::FLAG_KEYWORD_FUNCTION)
                    && (empty($options['parseField'])
                    && ! $alias)
                ) {
                    $isExpr = true;
                } elseif (($token->flags & Token::FLAG_KEYWORD_RESERVED) && ($brackets === 0)) {
                    if (empty(self::$ALLOWED_KEYWORDS[$token->keyword])) {
                        // A reserved keyword that is not allowed in the
                        // expression was found so the expression must have
                        // ended and a new clause is starting.
                        break;
                    }

                    if ($token->keyword === 'AS') {
                        if (! empty($options['breakOnAlias'])) {
                            break;
                        }

                        if ($alias) {
                            $parser->error('An alias was expected.', $token);
                            break;
                        }

                        $alias = true;
                        continue;
                    }

                    if ($token->keyword === 'CASE') {
                        // For a use of CASE like
                        // 'SELECT a = CASE .... END, b=1, `id`, ... FROM ...'
                        $tempCaseExpr = CaseExpression::parse($parser, $list);
                        $ret->expr .= CaseExpression::build($tempCaseExpr);
                        $isExpr = true;
                        continue;
                    }

                    $isExpr = true;
                } elseif (
                    $brackets === 0 && strlen((string) $ret->expr) > 0 && ! $alias
                    && ($ret->table === null || $ret->table === '')
                ) {
                    /* End of expression */
                    break;
                }
            }

            if (
                ($token->type === Token::TYPE_NUMBER)
                || ($token->type === Token::TYPE_BOOL)
                || (($token->type === Token::TYPE_SYMBOL)
                && ($token->flags & Token::FLAG_SYMBOL_VARIABLE))
                || (($token->type === Token::TYPE_SYMBOL)
                && ($token->flags & Token::FLAG_SYMBOL_PARAMETER))
                || (($token->type === Token::TYPE_OPERATOR)
                && ($token->value !== '.'))
            ) {
                if (! empty($options['parseField'])) {
                    break;
                }

                // Numbers, booleans and operators (except dot) are usually part
                // of expressions.
                $isExpr = true;
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if (! empty($options['breakOnParentheses']) && (($token->value === '(') || ($token->value === ')'))) {
                    // No brackets were expected.
                    break;
                }

                if ($token->value === '(') {
                    ++$brackets;
                    if (
                        empty($ret->function) && ($prev[1] !== null)
                        && (($prev[1]->type === Token::TYPE_NONE)
                        || ($prev[1]->type === Token::TYPE_SYMBOL)
                        || (($prev[1]->type === Token::TYPE_KEYWORD)
                        && ($prev[1]->flags & Token::FLAG_KEYWORD_FUNCTION)))
                    ) {
                        $ret->function = $prev[1]->value;
                    }
                } elseif ($token->value === ')') {
                    if ($brackets === 0) {
                        // Not our bracket
                        break;
                    }

                    --$brackets;
                    if ($brackets === 0) {
                        if (! empty($options['parenthesesDelimited'])) {
                            // The current token is the last bracket, the next
                            // one will be outside the expression.
                            $ret->expr .= $token->token;
                            ++$list->idx;
                            break;
                        }
                    } elseif ($brackets < 0) {
                        // $parser->error('Unexpected closing bracket.', $token);
                        // $brackets = 0;
                        break;
                    }
                } elseif ($token->value === ',') {
                    // Expressions are comma-delimited.
                    if ($brackets === 0) {
                        break;
                    }
                }
            }

            // Saving the previous tokens.
            $prev[0] = $prev[1];
            $prev[1] = $token;

            if ($alias) {
                // An alias is expected (the keyword `AS` was previously found).
                if (! empty($ret->alias)) {
                    $parser->error('An alias was previously found.', $token);
                    break;
                }

                $ret->alias = $token->value;
                $alias = false;
            } elseif ($isExpr) {
                // Handling aliases.
                if (
                    $brackets === 0
                    && ($prev[0] === null
                        || (($prev[0]->type !== Token::TYPE_OPERATOR || $prev[0]->token === ')')
                            && ($prev[0]->type !== Token::TYPE_KEYWORD
                                || ! ($prev[0]->flags & Token::FLAG_KEYWORD_RESERVED))))
                    && (($prev[1]->type === Token::TYPE_STRING)
                        || ($prev[1]->type === Token::TYPE_SYMBOL
                            && ! ($prev[1]->flags & Token::FLAG_SYMBOL_VARIABLE)
                            && ! ($prev[1]->flags & Token::FLAG_SYMBOL_PARAMETER))
                        || ($prev[1]->type === Token::TYPE_NONE
                            && strtoupper($prev[1]->token) !== 'OVER'))
                ) {
                    if (! empty($ret->alias)) {
                        $parser->error('An alias was previously found.', $token);
                        break;
                    }

                    $ret->alias = $prev[1]->value;
                } else {
                    $currIdx = $list->idx;
                    --$list->idx;
                    $beforeToken = $list->getPrevious();
                    $list->idx = $currIdx;
                    // columns names tokens are of type NONE, or SYMBOL (`col`), and the columns options
                    // would start with a token of type KEYWORD, in that case, we want to have a space
                    // between the tokens.
                    if (
                        $ret->expr !== null &&
                        $beforeToken &&
                        ($beforeToken->type === Token::TYPE_NONE ||
                        $beforeToken->type === Token::TYPE_SYMBOL || $beforeToken->type === Token::TYPE_STRING) &&
                        $token->type === Token::TYPE_KEYWORD
                    ) {
                        $ret->expr = rtrim($ret->expr, ' ') . ' ';
                    }

                    $ret->expr .= $token->token;
                }
            } elseif (! $isExpr) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '.')) {
                    // Found a `.` which means we expect a column name and
                    // the column name we parsed is actually the table name
                    // and the table name is actually a database name.
                    if (! empty($ret->database) || $dot) {
                        $parser->error('Unexpected dot.', $token);
                    }

                    $ret->database = $ret->table;
                    $ret->table = $ret->column;
                    $ret->column = null;
                    $dot = true;
                    $ret->expr .= $token->token;
                } else {
                    $field = empty($options['field']) ? 'column' : $options['field'];
                    if (empty($ret->$field)) {
                        $ret->$field = $token->value;
                        $ret->expr .= $token->token;
                        $dot = false;
                    } else {
                        // No alias is expected.
                        if (! empty($options['breakOnAlias'])) {
                            break;
                        }

                        if (! empty($ret->alias)) {
                            $parser->error('An alias was previously found.', $token);
                            break;
                        }

                        $ret->alias = $token->value;
                    }
                }
            }
        }

        if ($alias) {
            $parser->error('An alias was expected.', $list->tokens[$list->idx - 1]);
        }

        // White-spaces might be added at the end.
        $ret->expr = trim((string) $ret->expr);

        if ($ret->expr === '') {
            return null;
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param Expression|Expression[] $component the component to be built
     * @param array<string, mixed>    $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        if (is_array($component)) {
            return implode(', ', $component);
        }

        if ($component->expr !== '' && $component->expr !== null) {
            $ret = $component->expr;
        } else {
            $fields = [];
            if (isset($component->database) && ($component->database !== '')) {
                $fields[] = $component->database;
            }

            if (isset($component->table) && ($component->table !== '')) {
                $fields[] = $component->table;
            }

            if (isset($component->column) && ($component->column !== '')) {
                $fields[] = $component->column;
            }

            $ret = implode('.', Context::escape($fields));
        }

        if (! empty($component->alias)) {
            $ret .= ' AS ' . Context::escape($component->alias);
        }

        return $ret;
    }
}
