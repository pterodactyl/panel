<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;
use function is_array;

/**
 * `ORDER BY` keyword parser.
 *
 * @final
 */
class OrderKeyword extends Component
{
    /**
     * The expression that is used for ordering.
     *
     * @var Expression
     */
    public $expr;

    /**
     * The order type.
     *
     * @var string
     */
    public $type;

    /**
     * @param Expression $expr the expression that we are sorting by
     * @param string     $type the sorting type
     */
    public function __construct($expr = null, $type = 'ASC')
    {
        $this->expr = $expr;
        $this->type = $type;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return OrderKeyword[]
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
         *      0 --------------------[ expression ]-------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 -------------------[ ASC / DESC ]--------------------> 1
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
                $expr->expr = Expression::parse($parser, $list);
                $state = 1;
            } elseif ($state === 1) {
                if (
                    ($token->type === Token::TYPE_KEYWORD)
                    && (($token->keyword === 'ASC') || ($token->keyword === 'DESC'))
                ) {
                    $expr->type = $token->keyword;
                } elseif (($token->type === Token::TYPE_OPERATOR) && ($token->value === ',')) {
                    if (! empty($expr->expr)) {
                        $ret[] = $expr;
                    }

                    $expr = new static();
                    $state = 0;
                } else {
                    break;
                }
            }
        }

        // Last iteration was not processed.
        if (! empty($expr->expr)) {
            $ret[] = $expr;
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param OrderKeyword|OrderKeyword[] $component the component to be built
     * @param array<string, mixed>        $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        if (is_array($component)) {
            return implode(', ', $component);
        }

        return $component->expr . ' ' . $component->type;
    }
}
