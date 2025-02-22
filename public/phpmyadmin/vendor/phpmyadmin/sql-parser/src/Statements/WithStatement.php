<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Array2d;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\WithKeyword;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\Translator;

use function array_slice;
use function count;
use function preg_match;

/**
 * `WITH` statement.

 *  WITH [RECURSIVE] query_name [ (column_name [,...]) ] AS (SELECT ...) [, ...]
 */
final class WithStatement extends Statement
{
    /**
     * Options for `WITH` statements and their slot ID.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = ['RECURSIVE' => 1];

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$CLAUSES
     *
     * @var array<string, array<int, int|string>>
     * @psalm-var array<string, array{non-empty-string, (1|2|3)}>
     */
    public static $CLAUSES = [
        'WITH' => [
            'WITH',
            2,
        ],
        // Used for options.
        '_OPTIONS' => [
            '_OPTIONS',
            1,
        ],
        'AS' => [
            'AS',
            2,
        ],
    ];

    /** @var WithKeyword[] */
    public $withers = [];

    /**
     * holds the CTE parser.
     *
     * @var Parser|null
     */
    public $cteStatementParser;

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     *
     * @return void
     */
    public function parse(Parser $parser, TokensList $list)
    {
        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------- [ name ] -----------------> 1
         *
         *      1 ------------------ [ ( ] ------------------> 2
         *
         *      2 ------------------ [ AS ] -----------------> 3
         *
         *      3 ------------------ [ ( ] ------------------> 4
         *
         *      4 ------------------ [ , ] ------------------> 1
         *
         *      4 ----- [ SELECT/UPDATE/DELETE/INSERT ] -----> 5
         *
         * @var int
         */
        $state = 0;
        $wither = null;

        ++$list->idx; // Skipping `WITH`.

        // parse any options if provided
        $this->options = OptionsArray::parse($parser, $list, static::$OPTIONS);
        ++$list->idx;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if ($token->type === Token::TYPE_WHITESPACE || $token->type === Token::TYPE_COMMENT) {
                continue;
            }

            if ($state === 0) {
                if ($token->type !== Token::TYPE_NONE || ! preg_match('/^[a-zA-Z0-9_$]+$/', $token->token)) {
                    $parser->error('The name of the CTE was expected.', $token);
                    break;
                }

                $wither = $token->value;
                $this->withers[$wither] = new WithKeyword($wither);
                $state = 1;
            } elseif ($state === 1) {
                if ($token->type === Token::TYPE_OPERATOR && $token->value === '(') {
                    $columns = Array2d::parse($parser, $list);
                    if ($parser->errors !== []) {
                        break;
                    }

                    $this->withers[$wither]->columns = $columns;
                    $state = 2;
                } elseif ($token->type === Token::TYPE_KEYWORD && $token->keyword === 'AS') {
                    $state = 3;
                } else {
                    $parser->error('Unexpected token.', $token);
                    break;
                }
            } elseif ($state === 2) {
                if (! ($token->type === Token::TYPE_KEYWORD && $token->keyword === 'AS')) {
                    $parser->error('AS keyword was expected.', $token);
                    break;
                }

                $state = 3;
            } elseif ($state === 3) {
                $idxBeforeGetNext = $list->idx;

                $list->idx++; // Ignore the current token
                $nextKeyword = $list->getNext();

                if (! ($token->value === '(' && ($nextKeyword && $nextKeyword->value === 'SELECT'))) {
                    $parser->error('Subquery of the CTE was expected.', $token);
                    $list->idx = $idxBeforeGetNext;
                    break;
                }

                // Restore the index
                $list->idx = $idxBeforeGetNext;

                ++$list->idx;
                $subList = $this->getSubTokenList($list);
                if ($subList instanceof ParserException) {
                    $parser->errors[] = $subList;
                    break;
                }

                $subParser = new Parser($subList);

                if (count($subParser->errors)) {
                    foreach ($subParser->errors as $error) {
                        $parser->errors[] = $error;
                    }

                    break;
                }

                $this->withers[$wither]->statement = $subParser;

                $state = 4;
            } elseif ($state === 4) {
                if ($token->value === ',') {
                    // There's another WITH expression to parse, go back to state=0
                    $state = 0;
                    continue;
                }

                if (
                    $token->type === Token::TYPE_KEYWORD && (
                    $token->value === 'SELECT'
                    || $token->value === 'INSERT'
                    || $token->value === 'UPDATE'
                    || $token->value === 'DELETE'
                    )
                ) {
                    $state = 5;
                    --$list->idx;
                    continue;
                }

                $parser->error('An expression was expected.', $token);
                break;
            } elseif ($state === 5) {
                /**
                 * We need to parse all of the remaining tokens becuase mostly, they are only the CTE expression
                 * which's mostly is SELECT, or INSERT, UPDATE, or delete statement.
                 * e.g: INSERT .. ( SELECT 1 ) SELECT col1 FROM cte ON DUPLICATE KEY UPDATE col_name = 3.
                 * The issue is that, `ON DUPLICATE KEY UPDATE col_name = 3` is related to the main INSERT query
                 * not the cte expression (SELECT col1 FROM cte) we need to determine the end of the expression
                 * to parse `ON DUPLICATE KEY UPDATE` from the InsertStatement parser instead.
                 */

                // Index of the last parsed token by default would be the last token in the $list, because we're
                // assuming that all remaining tokens at state 4, are related to the expression.
                $idxOfLastParsedToken = $list->count - 1;
                // Index before search to be able to restore the index.
                $idxBeforeSearch = $list->idx;
                // Length of expression tokens is null by default, in order for the $subList to start
                // from $list->idx to the end of the $list.
                $lengthOfExpressionTokens = null;

                if ($list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'ON')) {
                    // (-1) because getNextOfTypeAndValue returned ON and increased the index.
                    $idxOfOn = $list->idx - 1;
                    // We want to make sure that it's `ON DUPLICATE KEY UPDATE`
                    $dubplicateToken = $list->getNext();
                    $keyToken = $list->getNext();
                    $updateToken = $list->getNext();
                    if (
                        $dubplicateToken && $dubplicateToken->keyword === 'DUPLICATE'
                        && ($keyToken && $keyToken->keyword === 'KEY')
                        && ($updateToken && $updateToken->keyword === 'UPDATE')
                    ) {
                        // Index of the last parsed token will be the token before the ON Keyword
                        $idxOfLastParsedToken = $idxOfOn - 1;
                        // The length of the expression tokens would be the difference
                        // between the first unrelated token `ON` and the idx
                        // before skipping the CTE tokens.
                        $lengthOfExpressionTokens = $idxOfOn - $idxBeforeSearch;
                    }
                }

                // Restore the index
                $list->idx = $idxBeforeSearch;

                $subList = new TokensList(array_slice($list->tokens, $list->idx, $lengthOfExpressionTokens));
                $subParser = new Parser($subList);
                if (count($subParser->errors)) {
                    foreach ($subParser->errors as $error) {
                        $parser->errors[] = $error;
                    }

                    break;
                }

                $this->cteStatementParser = $subParser;

                $list->idx = $idxOfLastParsedToken;
                break;
            }
        }

        // 5 is the only valid end state
        if ($state !== 5) {
             /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            $parser->error('Unexpected end of the WITH CTE.', $token);
        }

        --$list->idx;
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $initial = true;
        $str = 'WITH';

        if ($this->options !== null && $this->options->options !== []) {
            $str .= ' ' . OptionsArray::build($this->options);
        }

        foreach ($this->withers as $wither) {
            $str .= $initial ? ' ' : ', ';
            $str .= WithKeyword::build($wither);
            $initial = false;
        }

        $str .= ' ';

        if ($this->cteStatementParser) {
            foreach ($this->cteStatementParser->statements as $statement) {
                    $str .= $statement->build();
            }
        }

        return $str;
    }

    /**
     * Get tokens within the WITH expression to use them in another parser
     *
     * @return ParserException|TokensList
     */
    private function getSubTokenList(TokensList $list)
    {
        $idx = $list->idx;
        $token = $list->tokens[$list->idx];
        $openParenthesis = 0;

        while ($list->idx < $list->count) {
            if ($token->value === '(') {
                ++$openParenthesis;
            } elseif ($token->value === ')') {
                if (--$openParenthesis === -1) {
                    break;
                }
            }

            ++$list->idx;
            if (! isset($list->tokens[$list->idx])) {
                break;
            }

            $token = $list->tokens[$list->idx];
        }

        // performance improvement: return the error to avoid a try/catch in the loop
        if ($list->idx === $list->count) {
            --$list->idx;

            return new ParserException(
                Translator::gettext('A closing bracket was expected.'),
                $token
            );
        }

        $length = $list->idx - $idx;

        return new TokensList(array_slice($list->tokens, $idx, $length), $length);
    }
}
