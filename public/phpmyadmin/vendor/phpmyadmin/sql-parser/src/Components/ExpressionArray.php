<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function count;
use function implode;
use function is_array;
use function preg_match;
use function strlen;
use function substr;

/**
 * Parses a list of expressions delimited by a comma.
 *
 * @final
 */
class ExpressionArray extends Component
{
    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return Expression[]
     *
     * @throws ParserException
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = [];

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ array ]---------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 -----------------------[ else ]----------------------> (END)
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

            if (
                ($token->type === Token::TYPE_KEYWORD)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && ((~$token->flags & Token::FLAG_KEYWORD_FUNCTION))
                && ($token->value !== 'DUAL')
                && ($token->value !== 'NULL')
                && ($token->value !== 'CASE')
                && ($token->value !== 'NOT')
            ) {
                // No keyword is expected.
                break;
            }

            if ($state === 0) {
                if ($token->type === Token::TYPE_KEYWORD && $token->value === 'CASE') {
                    $expr = CaseExpression::parse($parser, $list, $options);
                } else {
                    $expr = Expression::parse($parser, $list, $options);
                }

                if ($expr === null) {
                    break;
                }

                $ret[] = $expr;
                $state = 1;
            } elseif ($state === 1) {
                if ($token->value !== ',') {
                    break;
                }

                $state = 0;
            }
        }

        if ($state === 0) {
            $parser->error('An expression was expected.', $list->tokens[$list->idx]);
        }

        --$list->idx;

        if (is_array($ret)) {
            $retIndex = count($ret) - 1;
            if (isset($ret[$retIndex])) {
                $expr = $ret[$retIndex]->expr;
                if (preg_match('/\s*--\s.*$/', $expr, $matches)) {
                    $found = $matches[0];
                    $ret[$retIndex]->expr = substr($expr, 0, strlen($expr) - strlen($found));
                }
            }
        }

        return $ret;
    }

    /**
     * @param Expression[]         $component the component to be built
     * @param array<string, mixed> $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        $ret = [];
        foreach ($component as $frag) {
            $ret[] = $frag::build($frag);
        }

        return implode(', ', $ret);
    }
}
