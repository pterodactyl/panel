<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function is_array;

/**
 * Parses a function call.
 *
 * @final
 */
class FunctionCall extends Component
{
    /**
     * The name of this function.
     *
     * @var string|null
     */
    public $name;

    /**
     * The list of parameters.
     *
     * @var ArrayObj|null
     */
    public $parameters;

    /**
     * @param string|null            $name       the name of the function to be called
     * @param string[]|ArrayObj|null $parameters the parameters of this function
     */
    public function __construct($name = null, $parameters = null)
    {
        $this->name = $name;
        if (is_array($parameters)) {
            $this->parameters = new ArrayObj($parameters);
        } elseif ($parameters instanceof ArrayObj) {
            $this->parameters = $parameters;
        }
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return FunctionCall
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ name ]-----------------------> 1
         *
         *      1 --------------------[ parameters ]-------------------> (END)
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
                $ret->name = $token->value;
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $ret->parameters = ArrayObj::parse($parser, $list);
                }

                break;
            }
        }

        return $ret;
    }

    /**
     * @param FunctionCall         $component the component to be built
     * @param array<string, mixed> $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        return $component->name . $component->parameters;
    }
}
