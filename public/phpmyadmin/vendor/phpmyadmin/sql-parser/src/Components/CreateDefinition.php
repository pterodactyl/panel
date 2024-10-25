<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;
use function is_array;
use function trim;

/**
 * Parses the create definition of a column or a key.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @final
 */
class CreateDefinition extends Component
{
    /**
     * All field options.
     *
     * @var array<string, bool|int|array<int, int|string|array<string, bool>>>
     * @psalm-var array<string, (bool|positive-int|array{
     *   0: positive-int,
     *   1: ('var'|'var='|'expr'|'expr='),
     *   2?: array<string, bool>
     * })>
     */
    public static $FIELD_OPTIONS = [
        // Tells the `OptionsArray` to not sort the options.
        // See the note below.
        '_UNSORTED' => true,

        'NOT NULL' => 1,
        'NULL' => 1,
        'DEFAULT' => [
            2,
            'expr',
            ['breakOnAlias' => true],
        ],
        /* Following are not according to grammar, but MySQL happily accepts
         * these at any location */
        'CHARSET' => [
            2,
            'var',
        ],
        'COLLATE' => [
            3,
            'var',
        ],
        'AUTO_INCREMENT' => 3,
        'PRIMARY' => 4,
        'PRIMARY KEY' => 4,
        'UNIQUE' => 4,
        'UNIQUE KEY' => 4,
        'COMMENT' => [
            5,
            'var',
        ],
        'COLUMN_FORMAT' => [
            6,
            'var',
        ],
        'ON UPDATE' => [
            7,
            'expr',
        ],

        // Generated columns options.
        'GENERATED ALWAYS' => 8,
        'AS' => [
            9,
            'expr',
            ['parenthesesDelimited' => true],
        ],
        'VIRTUAL' => 10,
        'PERSISTENT' => 11,
        'STORED' => 11,
        'CHECK' => [
            12,
            'expr',
            ['parenthesesDelimited' => true],
        ],
        'INVISIBLE' => 13,
        'ENFORCED' => 14,
        'NOT' => 15,
        'COMPRESSED' => 16,
        // Common entries.
        //
        // NOTE: Some of the common options are not in the same order which
        // causes troubles when checking if the options are in the right order.
        // I should find a way to define multiple sets of options and make the
        // parser select the right set.
        //
        // 'UNIQUE'                        => 4,
        // 'UNIQUE KEY'                    => 4,
        // 'COMMENT'                       => [5, 'var'],
        // 'NOT NULL'                      => 1,
        // 'NULL'                          => 1,
        // 'PRIMARY'                       => 4,
        // 'PRIMARY KEY'                   => 4,
    ];

    /**
     * The name of the new column.
     *
     * @var string|null
     */
    public $name;

    /**
     * Whether this field is a constraint or not.
     *
     * @var bool|null
     */
    public $isConstraint;

    /**
     * The data type of thew new column.
     *
     * @var DataType|null
     */
    public $type;

    /**
     * The key.
     *
     * @var Key|null
     */
    public $key;

    /**
     * The table that is referenced.
     *
     * @var Reference|null
     */
    public $references;

    /**
     * The options of this field.
     *
     * @var OptionsArray|null
     */
    public $options;

    /**
     * @param string|null       $name         the name of the field
     * @param OptionsArray|null $options      the options of this field
     * @param DataType|Key|null $type         the data type of this field or the key
     * @param bool              $isConstraint whether this field is a constraint or not
     * @param Reference|null    $references   references
     */
    public function __construct(
        $name = null,
        $options = null,
        $type = null,
        $isConstraint = false,
        $references = null
    ) {
        $this->name = $name;
        $this->options = $options;
        if ($type instanceof DataType) {
            $this->type = $type;
        } elseif ($type instanceof Key) {
            $this->key = $type;
            $this->isConstraint = $isConstraint;
            $this->references = $references;
        }
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return CreateDefinition[]
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
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 --------------------[ CONSTRAINT ]------------------> 1
         *      1 -----------------------[ key ]----------------------> 2
         *      1 -------------[ constraint / column name ]-----------> 2
         *
         *      2 --------------------[ data type ]-------------------> 3
         *
         *      3 ---------------------[ options ]--------------------> 4
         *
         *      4 --------------------[ REFERENCES ]------------------> 4
         *
         *      5 ------------------------[ , ]-----------------------> 1
         *      5 ------------------------[ ) ]-----------------------> 6 (-1)
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
                if (($token->type !== Token::TYPE_OPERATOR) || ($token->value !== '(')) {
                    $parser->error('An opening bracket was expected.', $token);

                    break;
                }

                $state = 1;
            } elseif ($state === 1) {
                if ($token->type === Token::TYPE_KEYWORD && $token->keyword === 'CONSTRAINT') {
                    $expr->isConstraint = true;
                } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_KEY)) {
                    $expr->key = Key::parse($parser, $list);
                    $state = 4;
                } elseif ($token->type === Token::TYPE_SYMBOL || $token->type === Token::TYPE_NONE) {
                    $expr->name = $token->value;
                    if (! $expr->isConstraint) {
                        $state = 2;
                    }
                } elseif ($token->type === Token::TYPE_KEYWORD) {
                    if ($token->flags & Token::FLAG_KEYWORD_RESERVED) {
                        // Reserved keywords can't be used
                        // as field names without backquotes
                        $parser->error(
                            'A symbol name was expected! '
                            . 'A reserved keyword can not be used '
                            . 'as a column name without backquotes.',
                            $token
                        );

                        return $ret;
                    }

                    // Non-reserved keywords are allowed without backquotes
                    $expr->name = $token->value;
                    $state = 2;
                } else {
                    $parser->error('A symbol name was expected!', $token);

                    return $ret;
                }
            } elseif ($state === 2) {
                $expr->type = DataType::parse($parser, $list);
                $state = 3;
            } elseif ($state === 3) {
                $expr->options = OptionsArray::parse($parser, $list, static::$FIELD_OPTIONS);
                $state = 4;
            } elseif ($state === 4) {
                if ($token->type === Token::TYPE_KEYWORD && $token->keyword === 'REFERENCES') {
                    ++$list->idx; // Skipping keyword 'REFERENCES'.
                    $expr->references = Reference::parse($parser, $list);
                } else {
                    --$list->idx;
                }

                $state = 5;
            } elseif ($state === 5) {
                if (! empty($expr->type) || ! empty($expr->key)) {
                    $ret[] = $expr;
                }

                $expr = new static();
                if ($token->value === ',') {
                    $state = 1;
                } elseif ($token->value === ')') {
                    $state = 6;
                    ++$list->idx;
                    break;
                } else {
                    $parser->error('A comma or a closing bracket was expected.', $token);
                    $state = 0;
                    break;
                }
            }
        }

        // Last iteration was not saved.
        if (! empty($expr->type) || ! empty($expr->key)) {
            $ret[] = $expr;
        }

        if (($state !== 0) && ($state !== 6)) {
            $parser->error('A closing bracket was expected.', $list->tokens[$list->idx - 1]);
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param CreateDefinition|CreateDefinition[] $component the component to be built
     * @param array<string, mixed>                $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        if (is_array($component)) {
            return "(\n  " . implode(",\n  ", $component) . "\n)";
        }

        $tmp = '';

        if ($component->isConstraint) {
            $tmp .= 'CONSTRAINT ';
        }

        if (isset($component->name) && ($component->name !== '')) {
            $tmp .= Context::escape($component->name) . ' ';
        }

        if (! empty($component->type)) {
            $tmp .= DataType::build(
                $component->type,
                ['lowercase' => true]
            ) . ' ';
        }

        if (! empty($component->key)) {
            $tmp .= $component->key . ' ';
        }

        if (! empty($component->references)) {
            $tmp .= 'REFERENCES ' . $component->references . ' ';
        }

        $tmp .= $component->options;

        return trim($tmp);
    }
}
