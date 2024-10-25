<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;
use function strtolower;
use function strtoupper;
use function trim;

/**
 * Parses a data type.
 *
 * @final
 */
class DataType extends Component
{
    /**
     * All data type options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $DATA_TYPE_OPTIONS = [
        'BINARY' => 1,
        'CHARACTER SET' => [
            2,
            'var',
        ],
        'CHARSET' => [
            2,
            'var',
        ],
        'COLLATE' => [
            3,
            'var',
        ],
        'UNSIGNED' => 4,
        'ZEROFILL' => 5,
    ];

    /**
     * The name of the data type.
     *
     * @var string
     */
    public $name;

    /**
     * The parameters of this data type.
     *
     * Some data types have no parameters.
     * Numeric types might have parameters for the maximum number of digits,
     * precision, etc.
     * String types might have parameters for the maximum length stored.
     * `ENUM` and `SET` have parameters for possible values.
     *
     * For more information, check the MySQL manual.
     *
     * @var int[]|string[]
     */
    public $parameters = [];

    /**
     * The options of this data type.
     *
     * @var OptionsArray
     */
    public $options;

    /**
     * @param string         $name       the name of this data type
     * @param int[]|string[] $parameters the parameters (size or possible values)
     * @param OptionsArray   $options    the options of this data type
     */
    public function __construct(
        $name = null,
        array $parameters = [],
        $options = null
    ) {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->options = $options;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return DataType|null
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ data type ]--------------------> 1
         *
         *      1 ----------------[ size and options ]----------------> 2
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                $ret->name = strtoupper((string) $token->value);
                if (($token->type !== Token::TYPE_KEYWORD) || (! ($token->flags & Token::FLAG_KEYWORD_DATA_TYPE))) {
                    $parser->error('Unrecognized data type.', $token);
                }

                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $parameters = ArrayObj::parse($parser, $list);
                    ++$list->idx;
                    $ret->parameters = ($ret->name === 'ENUM') || ($ret->name === 'SET') ?
                        $parameters->raw : $parameters->values;
                }

                $ret->options = OptionsArray::parse($parser, $list, static::$DATA_TYPE_OPTIONS);
                ++$list->idx;
                break;
            }
        }

        if (empty($ret->name)) {
            return null;
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param DataType             $component the component to be built
     * @param array<string, mixed> $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        $name = empty($options['lowercase']) ?
            $component->name : strtolower($component->name);

        $parameters = '';
        if (! empty($component->parameters)) {
            $parameters = '(' . implode(',', $component->parameters) . ')';
        }

        return trim($name . $parameters . ' ' . $component->options);
    }
}
