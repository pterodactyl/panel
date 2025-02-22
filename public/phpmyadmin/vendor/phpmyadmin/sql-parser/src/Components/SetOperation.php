<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;
use function in_array;
use function is_array;
use function trim;

/**
 * `SET` keyword parser.
 *
 * @final
 */
class SetOperation extends Component
{
    /**
     * The name of the column that is being updated.
     *
     * @var string
     */
    public $column;

    /**
     * The new value.
     *
     * @var string
     */
    public $value;

    /**
     * @param string $column Field's name..
     * @param string $value  new value
     */
    public function __construct($column = '', $value = '')
    {
        $this->column = $column;
        $this->value = $value;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return SetOperation[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = [];

        $expr = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------[ col_name ]--------------------> 0
         *      0 ---------------------[ = or := ]---------------------> 1
         *      1 -----------------------[ value ]---------------------> 1
         *      1 ------------------------[ , ]------------------------> 0
         *
         * @var int
         */
        $state = 0;

        /**
         * Token when the parser has seen the latest comma
         *
         * @var Token
         */
        $commaLastSeenAt = null;

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

            // No keyword is expected.
            if (
                ($token->type === Token::TYPE_KEYWORD)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && ($state === 0)
            ) {
                break;
            }

            if ($state === 0) {
                if (in_array($token->token, ['=', ':='], true)) {
                    $state = 1;
                } elseif ($token->value !== ',') {
                    $expr->column .= $token->token;
                } elseif ($token->value === ',') {
                    $commaLastSeenAt = $token;
                }
            } elseif ($state === 1) {
                $tmp = Expression::parse(
                    $parser,
                    $list,
                    ['breakOnAlias' => true]
                );
                if ($tmp === null) {
                    $parser->error('Missing expression.', $token);
                    break;
                }

                $expr->column = trim($expr->column);
                $expr->value = $tmp->expr;
                $ret[] = $expr;
                $expr = new static();
                $state = 0;
                $commaLastSeenAt = null;
            }
        }

        --$list->idx;

        // We saw a comma, but didn't see a column-value pair after it
        if ($commaLastSeenAt !== null) {
            $parser->error('Unexpected token.', $commaLastSeenAt);
        }

        return $ret;
    }

    /**
     * @param SetOperation|SetOperation[] $component the component to be built
     * @param array<string, mixed>        $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        if (is_array($component)) {
            return implode(', ', $component);
        }

        return $component->column . ' = ' . $component->value;
    }
}
