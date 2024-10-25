<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function implode;
use function is_array;
use function trim;

/**
 * Parses the create definition of a partition.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @final
 */
class PartitionDefinition extends Component
{
    /**
     * All field options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        'STORAGE ENGINE' => [
            1,
            'var',
        ],
        'ENGINE' => [
            1,
            'var',
        ],
        'COMMENT' => [
            2,
            'var',
        ],
        'DATA DIRECTORY' => [
            3,
            'var',
        ],
        'INDEX DIRECTORY' => [
            4,
            'var',
        ],
        'MAX_ROWS' => [
            5,
            'var',
        ],
        'MIN_ROWS' => [
            6,
            'var',
        ],
        'TABLESPACE' => [
            7,
            'var',
        ],
        'NODEGROUP' => [
            8,
            'var',
        ],
    ];

    /**
     * Whether this entry is a subpartition or a partition.
     *
     * @var bool
     */
    public $isSubpartition;

    /**
     * The name of this partition.
     *
     * @var string
     */
    public $name;

    /**
     * The type of this partition (what follows the `VALUES` keyword).
     *
     * @var string
     */
    public $type;

    /**
     * The expression used to defined this partition.
     *
     * @var Expression|string
     */
    public $expr;

    /**
     * The subpartitions of this partition.
     *
     * @var PartitionDefinition[]
     */
    public $subpartitions;

    /**
     * The options of this field.
     *
     * @var OptionsArray
     */
    public $options;

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     *
     * @return PartitionDefinition
     */
    public static function parse(Parser $parser, TokensList $list, array $options = [])
    {
        $ret = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------[ PARTITION | SUBPARTITION ]------------> 1
         *
         *      1 -----------------------[ name ]----------------------> 2
         *
         *      2 ----------------------[ VALUES ]---------------------> 3
         *
         *      3 ---------------------[ LESS THAN ]-------------------> 4
         *      3 ------------------------[ IN ]-----------------------> 4
         *
         *      4 -----------------------[ expr ]----------------------> 5
         *
         *      5 ----------------------[ options ]--------------------> 6
         *
         *      6 ------------------[ subpartitions ]------------------> (END)
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
                $ret->isSubpartition = ($token->type === Token::TYPE_KEYWORD) && ($token->keyword === 'SUBPARTITION');
                $state = 1;
            } elseif ($state === 1) {
                $ret->name = $token->value;

                // Looking ahead for a 'VALUES' keyword.
                // Loop until the end of the partition name (delimited by a whitespace)
                while ($nextToken = $list->tokens[++$list->idx]) {
                    if ($nextToken->type !== Token::TYPE_NONE) {
                        break;
                    }

                    $ret->name .= $nextToken->value;
                }

                $idx = $list->idx--;
                // Get the first token after the white space.
                $nextToken = $list->tokens[++$idx];

                $state = ($nextToken->type === Token::TYPE_KEYWORD)
                    && ($nextToken->value === 'VALUES')
                    ? 2 : 5;
            } elseif ($state === 2) {
                $state = 3;
            } elseif ($state === 3) {
                $ret->type = $token->value;
                $state = 4;
            } elseif ($state === 4) {
                if ($token->value === 'MAXVALUE') {
                    $ret->expr = $token->value;
                } else {
                    $ret->expr = Expression::parse(
                        $parser,
                        $list,
                        [
                            'parenthesesDelimited' => true,
                            'breakOnAlias' => true,
                        ]
                    );
                }

                $state = 5;
            } elseif ($state === 5) {
                $ret->options = OptionsArray::parse($parser, $list, static::$OPTIONS);
                $state = 6;
            } elseif ($state === 6) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $ret->subpartitions = ArrayObj::parse(
                        $parser,
                        $list,
                        ['type' => 'PhpMyAdmin\\SqlParser\\Components\\PartitionDefinition']
                    );
                    ++$list->idx;
                }

                break;
            }
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param PartitionDefinition|PartitionDefinition[] $component the component to be built
     * @param array<string, mixed>                      $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        if (is_array($component)) {
            return "(\n" . implode(",\n", $component) . "\n)";
        }

        if ($component->isSubpartition) {
            return trim('SUBPARTITION ' . $component->name . ' ' . $component->options);
        }

        $subpartitions = empty($component->subpartitions) ? '' : ' ' . self::build($component->subpartitions);

        return trim(
            'PARTITION ' . $component->name
            . (empty($component->type) ? '' : ' VALUES ' . $component->type . ' ' . $component->expr . ' ')
            . (! empty($component->options) && ! empty($component->type) ? '' : ' ')
            . $component->options . $subpartitions
        );
    }
}
