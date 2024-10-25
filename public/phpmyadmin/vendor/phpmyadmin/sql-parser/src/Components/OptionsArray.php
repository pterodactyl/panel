<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\Translator;

use function array_merge_recursive;
use function count;
use function implode;
use function is_array;
use function ksort;
use function sprintf;
use function strcasecmp;
use function strtoupper;

/**
 * Parses a list of options.
 *
 * @final
 */
class OptionsArray extends Component
{
    /**
     * ArrayObj of selected options.
     *
     * @var array<int, mixed>
     */
    public $options = [];

    /**
     * @param array<int, mixed> $options The array of options. Options that have a value
     *                       must be an array with at least two keys `name` and
     *                       `expr` or `value`.
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return OptionsArray
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = new static();

        /**
         * The ID that will be assigned to duplicate options.
         *
         * @var int
         */
        $lastAssignedId = count($options) + 1;

        /**
         * The option that was processed last time.
         */
        $lastOption = null;

        /**
         * The index of the option that was processed last time.
         *
         * @var int
         */
        $lastOptionId = 0;

        /**
         * Counts brackets.
         *
         * @var int
         */
        $brackets = 0;

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------[ option ]----------------------> 1
         *
         *      1 -------------------[ = (optional) ]------------------> 2
         *
         *      2 ----------------------[ value ]----------------------> 0
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

            // Skipping comments.
            if ($token->type === Token::TYPE_COMMENT) {
                continue;
            }

            // Skipping whitespace if not parsing value.
            if (($token->type === Token::TYPE_WHITESPACE) && ($brackets === 0)) {
                continue;
            }

            if ($lastOption === null) {
                $upper = strtoupper($token->token);
                if (! isset($options[$upper])) {
                    // There is no option to be processed.
                    break;
                }

                $lastOption = $options[$upper];
                $lastOptionId = is_array($lastOption) ?
                    $lastOption[0] : $lastOption;
                $state = 0;

                // Checking for option conflicts.
                // For example, in `SELECT` statements the keywords `ALL`
                // and `DISTINCT` conflict and if used together, they
                // produce an invalid query.
                //
                // Usually, tokens can be identified in the array by the
                // option ID, but if conflicts occur, a generated option ID
                // is used.
                //
                // The first pseudo duplicate ID is the maximum value of the
                // real options (e.g.  if there are 5 options, the first
                // fake ID is 6).
                if (isset($ret->options[$lastOptionId])) {
                    $parser->error(
                        sprintf(
                            Translator::gettext('This option conflicts with "%1$s".'),
                            is_array($ret->options[$lastOptionId])
                            ? $ret->options[$lastOptionId]['name']
                            : $ret->options[$lastOptionId]
                        ),
                        $token
                    );
                    $lastOptionId = $lastAssignedId++;
                }
            }

            if ($state === 0) {
                if (! is_array($lastOption)) {
                    // This is a just keyword option without any value.
                    // This is the beginning and the end of it.
                    $ret->options[$lastOptionId] = $token->value;
                    $lastOption = null;
                    $state = 0;
                } elseif (($lastOption[1] === 'var') || ($lastOption[1] === 'var=')) {
                    // This is a keyword that is followed by a value.
                    // This is only the beginning. The value is parsed in state
                    // 1 and 2. State 1 is used to skip the first equals sign
                    // and state 2 to parse the actual value.
                    $ret->options[$lastOptionId] = [
                        // @var string The name of the option.
                        'name' => $token->value,
                        // @var bool Whether it contains an equal sign.
                        //           This is used by the builder to rebuild it.
                        'equals' => $lastOption[1] === 'var=',
                        // @var string Raw value.
                        'expr' => '',
                        // @var string Processed value.
                        'value' => '',
                    ];
                    $state = 1;
                } elseif ($lastOption[1] === 'expr' || $lastOption[1] === 'expr=') {
                    // This is a keyword that is followed by an expression.
                    // The expression is used by the specialized parser.

                    // Skipping this option in order to parse the expression.
                    ++$list->idx;
                    $ret->options[$lastOptionId] = [
                        // @var string The name of the option.
                        'name' => $token->value,
                        // @var bool Whether it contains an equal sign.
                        //           This is used by the builder to rebuild it.
                        'equals' => $lastOption[1] === 'expr=',
                        // @var Expression The parsed expression.
                        'expr' => '',
                    ];
                    $state = 1;
                }
            } elseif ($state === 1) {
                $state = 2;
                if ($token->token === '=') {
                    $ret->options[$lastOptionId]['equals'] = true;
                    continue;
                }
            }

            // This is outside the `elseif` group above because the change might
            // change this iteration.
            if ($state !== 2) {
                continue;
            }

            if ($lastOption[1] === 'expr' || $lastOption[1] === 'expr=') {
                $ret->options[$lastOptionId]['expr'] = Expression::parse(
                    $parser,
                    $list,
                    empty($lastOption[2]) ? [] : $lastOption[2]
                );
                if ($ret->options[$lastOptionId]['expr'] !== null) {
                    $ret->options[$lastOptionId]['value']
                        = $ret->options[$lastOptionId]['expr']->expr;
                }

                $lastOption = null;
                $state = 0;
            } else {
                if ($token->token === '(') {
                    ++$brackets;
                } elseif ($token->token === ')') {
                    --$brackets;
                }

                $ret->options[$lastOptionId]['expr'] .= $token->token;

                if (
                    ! (($token->token === '(') && ($brackets === 1)
                    || (($token->token === ')') && ($brackets === 0)))
                ) {
                    // First pair of brackets is being skipped.
                    $ret->options[$lastOptionId]['value'] .= $token->value;
                }

                // Checking if we finished parsing.
                if ($brackets === 0) {
                    $lastOption = null;
                }
            }
        }

        /*
         * We reached the end of statement without getting a value
         * for an option for which a value was required
         */
        if (
            $state === 1
            && $lastOption
            && ($lastOption[1] === 'expr'
            || $lastOption[1] === 'var'
            || $lastOption[1] === 'var='
            || $lastOption[1] === 'expr=')
        ) {
            $parser->error(
                sprintf(
                    'Value/Expression for the option %1$s was expected.',
                    $ret->options[$lastOptionId]['name']
                ),
                $list->tokens[$list->idx - 1]
            );
        }

        if (empty($options['_UNSORTED'])) {
            ksort($ret->options);
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param OptionsArray         $component the component to be built
     * @param array<string, mixed> $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        if (empty($component->options)) {
            return '';
        }

        $options = [];
        foreach ($component->options as $option) {
            if (! is_array($option)) {
                $options[] = $option;
            } else {
                $options[] = $option['name']
                    . (! empty($option['equals']) && $option['equals'] ? '=' : ' ')
                    . (! empty($option['expr']) ? $option['expr'] : $option['value']);
            }
        }

        return implode(' ', $options);
    }

    /**
     * Checks if it has the specified option and returns it value or true.
     *
     * @param string $key     the key to be checked
     * @param bool   $getExpr Gets the expression instead of the value.
     *                        The value is the processed form of the expression.
     *
     * @return mixed
     */
    public function has($key, $getExpr = false)
    {
        foreach ($this->options as $option) {
            if (is_array($option)) {
                if (! strcasecmp($key, $option['name'])) {
                    return $getExpr ? $option['expr'] : $option['value'];
                }
            } elseif (! strcasecmp($key, $option)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes the option from the array.
     *
     * @param string $key the key to be removed
     *
     * @return bool whether the key was found and deleted or not
     */
    public function remove($key)
    {
        foreach ($this->options as $idx => $option) {
            if (is_array($option)) {
                if (! strcasecmp($key, $option['name'])) {
                    unset($this->options[$idx]);

                    return true;
                }
            } elseif (! strcasecmp($key, $option)) {
                unset($this->options[$idx]);

                return true;
            }
        }

        return false;
    }

    /**
     * Merges the specified options with these ones. Values with same ID will be
     * replaced.
     *
     * @param array<int, mixed>|OptionsArray $options the options to be merged
     *
     * @return void
     */
    public function merge($options)
    {
        if (is_array($options)) {
            $this->options = array_merge_recursive($this->options, $options);
        } elseif ($options instanceof self) {
            $this->options = array_merge_recursive($this->options, $options->options);
        }
    }

    /**
     * Checks tf there are no options set.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->options);
    }
}
