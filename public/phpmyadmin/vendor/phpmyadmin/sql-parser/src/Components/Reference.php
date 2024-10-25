<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;
use function trim;

/**
 * `REFERENCES` keyword parser.
 *
 * @final
 */
class Reference extends Component
{
    /**
     * All references options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $REFERENCES_OPTIONS = [
        'MATCH' => [
            1,
            'var',
        ],
        'ON DELETE' => [
            2,
            'var',
        ],
        'ON UPDATE' => [
            3,
            'var',
        ],
    ];

    /**
     * The referenced table.
     *
     * @var Expression
     */
    public $table;

    /**
     * The referenced columns.
     *
     * @var string[]
     */
    public $columns;

    /**
     * The options of the referencing.
     *
     * @var OptionsArray
     */
    public $options;

    /**
     * @param Expression   $table   the name of the table referenced
     * @param string[]     $columns the columns referenced
     * @param OptionsArray $options the options
     */
    public function __construct($table = null, array $columns = [], $options = null)
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->options = $options;
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return Reference
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ table ]---------------------> 1
         *
         *      1 ---------------------[ columns ]--------------------> 2
         *
         *      2 ---------------------[ options ]--------------------> (END)
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
                $ret->table = Expression::parse(
                    $parser,
                    $list,
                    [
                        'parseField' => 'table',
                        'breakOnAlias' => true,
                    ]
                );
                $state = 1;
            } elseif ($state === 1) {
                $ret->columns = ArrayObj::parse($parser, $list)->values;
                $state = 2;
            } elseif ($state === 2) {
                $ret->options = OptionsArray::parse($parser, $list, static::$REFERENCES_OPTIONS);
                ++$list->idx;
                break;
            }
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param Reference            $component the component to be built
     * @param array<string, mixed> $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        return trim(
            $component->table
            . ' (' . implode(', ', Context::escape($component->columns)) . ') '
            . $component->options
        );
    }
}
