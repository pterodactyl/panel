<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\ArrayObj;
use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Components\DataType;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Components\ParameterDefinition;
use PhpMyAdmin\SqlParser\Components\PartitionDefinition;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function is_array;
use function trim;

/**
 * `CREATE` statement.
 */
class CreateStatement extends Statement
{
    /**
     * Options for `CREATE` statements.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $OPTIONS = [
        // CREATE TABLE
        'TEMPORARY' => 1,

        // CREATE VIEW
        'OR REPLACE' => 2,
        'ALGORITHM' => [
            3,
            'var=',
        ],
        // `DEFINER` is also used for `CREATE FUNCTION / PROCEDURE`
        'DEFINER' => [
            4,
            'expr=',
        ],
        // Used in `CREATE VIEW`
        'SQL SECURITY' => [
            5,
            'var',
        ],

        'DATABASE' => 6,
        'EVENT' => 6,
        'FUNCTION' => 6,
        'INDEX' => 6,
        'UNIQUE INDEX' => 6,
        'FULLTEXT INDEX' => 6,
        'SPATIAL INDEX' => 6,
        'PROCEDURE' => 6,
        'SERVER' => 6,
        'TABLE' => 6,
        'TABLESPACE' => 6,
        'TRIGGER' => 6,
        'USER' => 6,
        'VIEW' => 6,
        'SCHEMA' => 6,

        // CREATE TABLE
        'IF NOT EXISTS' => 7,
    ];

    /**
     * All database options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $DB_OPTIONS = [
        'CHARACTER SET' => [
            1,
            'var=',
        ],
        'CHARSET' => [
            1,
            'var=',
        ],
        'DEFAULT CHARACTER SET' => [
            1,
            'var=',
        ],
        'DEFAULT CHARSET' => [
            1,
            'var=',
        ],
        'DEFAULT COLLATE' => [
            2,
            'var=',
        ],
        'COLLATE' => [
            2,
            'var=',
        ],
    ];

    /**
     * All table options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $TABLE_OPTIONS = [
        'ENGINE' => [
            1,
            'var=',
        ],
        'AUTO_INCREMENT' => [
            2,
            'var=',
        ],
        'AVG_ROW_LENGTH' => [
            3,
            'var',
        ],
        'CHARACTER SET' => [
            4,
            'var=',
        ],
        'CHARSET' => [
            4,
            'var=',
        ],
        'DEFAULT CHARACTER SET' => [
            4,
            'var=',
        ],
        'DEFAULT CHARSET' => [
            4,
            'var=',
        ],
        'CHECKSUM' => [
            5,
            'var',
        ],
        'DEFAULT COLLATE' => [
            6,
            'var=',
        ],
        'COLLATE' => [
            6,
            'var=',
        ],
        'COMMENT' => [
            7,
            'var=',
        ],
        'CONNECTION' => [
            8,
            'var',
        ],
        'DATA DIRECTORY' => [
            9,
            'var',
        ],
        'DELAY_KEY_WRITE' => [
            10,
            'var',
        ],
        'INDEX DIRECTORY' => [
            11,
            'var',
        ],
        'INSERT_METHOD' => [
            12,
            'var',
        ],
        'KEY_BLOCK_SIZE' => [
            13,
            'var',
        ],
        'MAX_ROWS' => [
            14,
            'var',
        ],
        'MIN_ROWS' => [
            15,
            'var',
        ],
        'PACK_KEYS' => [
            16,
            'var',
        ],
        'PASSWORD' => [
            17,
            'var',
        ],
        'ROW_FORMAT' => [
            18,
            'var',
        ],
        'TABLESPACE' => [
            19,
            'var',
        ],
        'STORAGE' => [
            20,
            'var',
        ],
        'UNION' => [
            21,
            'var',
        ],
        'PAGE_COMPRESSED' => [
            22,
            'var',
        ],
        'PAGE_COMPRESSION_LEVEL' => [
            23,
            'var',
        ],
    ];

    /**
     * All function options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $FUNC_OPTIONS = [
        'NOT' => [
            2,
            'var',
        ],
        'FUNCTION' => [
            3,
            'var=',
        ],
        'PROCEDURE' => [
            3,
            'var=',
        ],
        'CONTAINS SQL' => 4,
        'NO SQL' => 4,
        'READS SQL DATA' => 4,
        'MODIFIES SQL DATA' => 4,
        'SQL SECURITY' => [
            6,
            'var',
        ],
        'LANGUAGE' => [
            7,
            'var',
        ],
        'COMMENT' => [
            8,
            'var',
        ],

        'CREATE' => 1,
        'DETERMINISTIC' => 2,
    ];

    /**
     * All trigger options.
     *
     * @var array<string, int|array<int, int|string>>
     * @psalm-var array<string, (positive-int|array{positive-int, ('var'|'var='|'expr'|'expr=')})>
     */
    public static $TRIGGER_OPTIONS = [
        'BEFORE' => 1,
        'AFTER' => 1,
        'INSERT' => 2,
        'UPDATE' => 2,
        'DELETE' => 2,
    ];

    /**
     * The name of the entity that is created.
     *
     * Used by all `CREATE` statements.
     *
     * @var Expression|null
     */
    public $name;

    /**
     * The options of the entity (table, procedure, function, etc.).
     *
     * Used by `CREATE TABLE`, `CREATE FUNCTION` and `CREATE PROCEDURE`.
     *
     * @see static::$TABLE_OPTIONS
     * @see static::$FUNC_OPTIONS
     * @see static::$TRIGGER_OPTIONS
     *
     * @var OptionsArray|null
     */
    public $entityOptions;

    /**
     * If `CREATE TABLE`, a list of columns and keys.
     * If `CREATE VIEW`, a list of columns.
     *
     * Used by `CREATE TABLE` and `CREATE VIEW`.
     *
     * @var CreateDefinition[]|ArrayObj|null
     */
    public $fields;

    /**
     * If `CREATE TABLE WITH`.
     * If `CREATE TABLE AS WITH`.
     * If `CREATE VIEW AS WITH`.
     *
     * Used by `CREATE TABLE`, `CREATE VIEW`
     *
     * @var WithStatement|null
     */
    public $with;

    /**
     * If `CREATE TABLE ... SELECT`.
     * If `CREATE VIEW AS ` ... SELECT`.
     *
     * Used by `CREATE TABLE`, `CREATE VIEW`
     *
     * @var SelectStatement|null
     */
    public $select;

    /**
     * If `CREATE TABLE ... LIKE`.
     *
     * Used by `CREATE TABLE`
     *
     * @var Expression|null
     */
    public $like;

    /**
     * Expression used for partitioning.
     *
     * @var string|null
     */
    public $partitionBy;

    /**
     * The number of partitions.
     *
     * @var int|null
     */
    public $partitionsNum;

    /**
     * Expression used for subpartitioning.
     *
     * @var string|null
     */
    public $subpartitionBy;

    /**
     * The number of subpartitions.
     *
     * @var int|null
     */
    public $subpartitionsNum;

    /**
     * The partition of the new table.
     *
     * @var PartitionDefinition[]|null
     */
    public $partitions;

    /**
     * If `CREATE TRIGGER` the name of the table.
     *
     * Used by `CREATE TRIGGER`.
     *
     * @var Expression|null
     */
    public $table;

    /**
     * The return data type of this routine.
     *
     * Used by `CREATE FUNCTION`.
     *
     * @var DataType|null
     */
    public $return;

    /**
     * The parameters of this routine.
     *
     * Used by `CREATE FUNCTION` and `CREATE PROCEDURE`.
     *
     * @var ParameterDefinition[]|null
     */
    public $parameters;

    /**
     * The body of this function or procedure.
     * For views, it is the select statement that creates the view.
     * Used by `CREATE FUNCTION`, `CREATE PROCEDURE` and `CREATE VIEW`.
     *
     * @var Token[]|string
     */
    public $body = [];

    /**
     * @return string
     */
    public function build()
    {
        $fields = '';
        if (! empty($this->fields)) {
            if (is_array($this->fields)) {
                $fields = CreateDefinition::build($this->fields) . ' ';
            } elseif ($this->fields instanceof ArrayObj) {
                $fields = ArrayObj::build($this->fields);
            }
        }

        if ($this->options->has('DATABASE') || $this->options->has('SCHEMA')) {
            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . OptionsArray::build($this->entityOptions);
        }

        if ($this->options->has('TABLE')) {
            if ($this->select !== null) {
                return 'CREATE '
                    . OptionsArray::build($this->options) . ' '
                    . Expression::build($this->name) . ' '
                    . $this->select->build();
            }

            if ($this->like !== null) {
                return 'CREATE '
                    . OptionsArray::build($this->options) . ' '
                    . Expression::build($this->name) . ' LIKE '
                    . Expression::build($this->like);
            }

            if ($this->with !== null) {
                return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . $this->with->build();
            }

            $partition = '';

            if (! empty($this->partitionBy)) {
                $partition .= "\nPARTITION BY " . $this->partitionBy;
            }

            if (! empty($this->partitionsNum)) {
                $partition .= "\nPARTITIONS " . $this->partitionsNum;
            }

            if (! empty($this->subpartitionBy)) {
                $partition .= "\nSUBPARTITION BY " . $this->subpartitionBy;
            }

            if (! empty($this->subpartitionsNum)) {
                $partition .= "\nSUBPARTITIONS " . $this->subpartitionsNum;
            }

            if (! empty($this->partitions)) {
                $partition .= "\n" . PartitionDefinition::build($this->partitions);
            }

            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . $fields
                . OptionsArray::build($this->entityOptions)
                . $partition;
        } elseif ($this->options->has('VIEW')) {
            $builtStatement = '';
            if ($this->select !== null) {
                $builtStatement = $this->select->build();
            } elseif ($this->with !== null) {
                $builtStatement = $this->with->build();
            }

            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . $fields . ' AS ' . $builtStatement
                . (! empty($this->body) ? TokensList::build($this->body) : '') . ' '
                . OptionsArray::build($this->entityOptions);
        } elseif ($this->options->has('TRIGGER')) {
            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . OptionsArray::build($this->entityOptions) . ' '
                . 'ON ' . Expression::build($this->table) . ' '
                . 'FOR EACH ROW ' . TokensList::build($this->body);
        } elseif ($this->options->has('PROCEDURE') || $this->options->has('FUNCTION')) {
            $tmp = '';
            if ($this->options->has('FUNCTION')) {
                $tmp = 'RETURNS ' . DataType::build($this->return);
            }

            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . ParameterDefinition::build($this->parameters) . ' '
                . $tmp . ' ' . OptionsArray::build($this->entityOptions) . ' '
                . TokensList::build($this->body);
        }

        return 'CREATE '
            . OptionsArray::build($this->options) . ' '
            . Expression::build($this->name) . ' '
            . TokensList::build($this->body);
    }

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list)
    {
        ++$list->idx; // Skipping `CREATE`.

        // Parsing options.
        $this->options = OptionsArray::parse($parser, $list, static::$OPTIONS);
        ++$list->idx; // Skipping last option.

        $isDatabase = $this->options->has('DATABASE') || $this->options->has('SCHEMA');
        $fieldName = $isDatabase ? 'database' : 'table';

        // Parsing the field name.
        $this->name = Expression::parse(
            $parser,
            $list,
            [
                'parseField' => $fieldName,
                'breakOnAlias' => true,
            ]
        );

        if (! isset($this->name) || ($this->name === '')) {
            $parser->error('The name of the entity was expected.', $list->tokens[$list->idx]);
        } else {
            ++$list->idx; // Skipping field.
        }

        /**
         * Token parsed at this moment.
         */
        $token = $list->tokens[$list->idx];
        $nextidx = $list->idx + 1;
        while ($nextidx < $list->count && $list->tokens[$nextidx]->type === Token::TYPE_WHITESPACE) {
            ++$nextidx;
        }

        if ($isDatabase) {
            $this->entityOptions = OptionsArray::parse($parser, $list, static::$DB_OPTIONS);
        } elseif ($this->options->has('TABLE')) {
            if (($token->type === Token::TYPE_KEYWORD) && ($token->keyword === 'SELECT')) {
                /* CREATE TABLE ... SELECT */
                $this->select = new SelectStatement($parser, $list);
            } elseif ($token->type === Token::TYPE_KEYWORD && ($token->keyword === 'WITH')) {
                /* CREATE TABLE WITH */
                $this->with = new WithStatement($parser, $list);
            } elseif (
                ($token->type === Token::TYPE_KEYWORD) && ($token->keyword === 'AS')
                && ($list->tokens[$nextidx]->type === Token::TYPE_KEYWORD)
            ) {
                if ($list->tokens[$nextidx]->value === 'SELECT') {
                    /* CREATE TABLE ... AS SELECT */
                    $list->idx = $nextidx;
                    $this->select = new SelectStatement($parser, $list);
                } elseif ($list->tokens[$nextidx]->value === 'WITH') {
                    /* CREATE TABLE WITH */
                    $list->idx = $nextidx;
                    $this->with = new WithStatement($parser, $list);
                }
            } elseif ($token->type === Token::TYPE_KEYWORD && $token->keyword === 'LIKE') {
                /* CREATE TABLE `new_tbl` LIKE 'orig_tbl' */
                $list->idx = $nextidx;
                $this->like = Expression::parse(
                    $parser,
                    $list,
                    [
                        'parseField' => 'table',
                        'breakOnAlias' => true,
                    ]
                );
                // The 'LIKE' keyword was found, but no table_name was found next to it
                if ($this->like === null) {
                    $parser->error('A table name was expected.', $list->tokens[$list->idx]);
                }
            } else {
                $this->fields = CreateDefinition::parse($parser, $list);
                if (empty($this->fields)) {
                    $parser->error('At least one column definition was expected.', $list->tokens[$list->idx]);
                }

                ++$list->idx;

                $this->entityOptions = OptionsArray::parse($parser, $list, static::$TABLE_OPTIONS);

                /**
                 * The field that is being filled (`partitionBy` or
                 * `subpartitionBy`).
                 *
                 * @var string
                 */
                $field = null;

                /**
                 * The number of brackets. `false` means no bracket was found
                 * previously. At least one bracket is required to validate the
                 * expression.
                 *
                 * @var int|bool
                 */
                $brackets = false;

                /*
                 * Handles partitions.
                 */
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

                    if (($token->type === Token::TYPE_KEYWORD) && ($token->keyword === 'PARTITION BY')) {
                        $field = 'partitionBy';
                        $brackets = false;
                    } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->keyword === 'SUBPARTITION BY')) {
                        $field = 'subpartitionBy';
                        $brackets = false;
                    } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->keyword === 'PARTITIONS')) {
                        $token = $list->getNextOfType(Token::TYPE_NUMBER);
                        --$list->idx; // `getNextOfType` also advances one position.
                        $this->partitionsNum = $token->value;
                    } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->keyword === 'SUBPARTITIONS')) {
                        $token = $list->getNextOfType(Token::TYPE_NUMBER);
                        --$list->idx; // `getNextOfType` also advances one position.
                        $this->subpartitionsNum = $token->value;
                    } elseif (! empty($field)) {
                        /*
                         * Handling the content of `PARTITION BY` and `SUBPARTITION BY`.
                         */

                        // Counting brackets.
                        if ($token->type === Token::TYPE_OPERATOR) {
                            if ($token->value === '(') {
                                // This is used instead of `++$brackets` because,
                                // initially, `$brackets` is `false` cannot be
                                // incremented.
                                $brackets += 1;
                            } elseif ($token->value === ')') {
                                --$brackets;
                            }
                        }

                        // Building the expression used for partitioning.
                        $this->$field .= $token->type === Token::TYPE_WHITESPACE ? ' ' : $token->token;

                        // Last bracket was read, the expression ended.
                        // Comparing with `0` and not `false`, because `false` means
                        // that no bracket was found and at least one must is
                        // required.
                        if ($brackets === 0) {
                            $this->$field = trim($this->$field);
                            $field = null;
                        }
                    } elseif (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                        if (! empty($this->partitionBy)) {
                            $this->partitions = ArrayObj::parse(
                                $parser,
                                $list,
                                ['type' => 'PhpMyAdmin\\SqlParser\\Components\\PartitionDefinition']
                            );
                        }

                        break;
                    }
                }
            }
        } elseif ($this->options->has('PROCEDURE') || $this->options->has('FUNCTION')) {
            $this->parameters = ParameterDefinition::parse($parser, $list);
            if ($this->options->has('FUNCTION')) {
                $prevToken = $token;
                $token = $list->getNextOfType(Token::TYPE_KEYWORD);
                if ($token === null || $token->keyword !== 'RETURNS') {
                    $parser->error('A "RETURNS" keyword was expected.', $token ?? $prevToken);
                } else {
                    ++$list->idx;
                    $this->return = DataType::parse($parser, $list);
                }
            }

            ++$list->idx;

            $this->entityOptions = OptionsArray::parse($parser, $list, static::$FUNC_OPTIONS);
            ++$list->idx;

            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                $this->body[] = $token;
            }
        } elseif ($this->options->has('VIEW')) {
            /** @var Token $token */
            $token = $list->getNext(); // Skipping whitespaces and comments.

            // Parsing columns list.
            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                --$list->idx; // getNext() also goes forward one field.
                $this->fields = ArrayObj::parse($parser, $list);
                ++$list->idx; // Skipping last token from the array.
                $list->getNext();
            }

            // Parsing the SELECT expression if the view started with it.
            if (
                $token->type === Token::TYPE_KEYWORD
                && $token->keyword === 'AS'
                && $list->tokens[$nextidx]->type === Token::TYPE_KEYWORD
            ) {
                if ($list->tokens[$nextidx]->value === 'SELECT') {
                    $list->idx = $nextidx;
                    $this->select = new SelectStatement($parser, $list);
                    ++$list->idx; // Skipping last token from the select.
                } elseif ($list->tokens[$nextidx]->value === 'WITH') {
                    ++$list->idx;
                    $this->with = new WithStatement($parser, $list);
                }
            }

            // Parsing all other tokens
            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                if ($token->type === Token::TYPE_DELIMITER) {
                    break;
                }

                $this->body[] = $token;
            }
        } elseif ($this->options->has('TRIGGER')) {
            // Parsing the time and the event.
            $this->entityOptions = OptionsArray::parse($parser, $list, static::$TRIGGER_OPTIONS);
            ++$list->idx;

            $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'ON');
            ++$list->idx; // Skipping `ON`.

            // Parsing the name of the table.
            $this->table = Expression::parse(
                $parser,
                $list,
                [
                    'parseField' => 'table',
                    'breakOnAlias' => true,
                ]
            );
            ++$list->idx;

            $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'FOR EACH ROW');
            ++$list->idx; // Skipping `FOR EACH ROW`.

            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                $this->body[] = $token;
            }
        } else {
            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                if ($token->type === Token::TYPE_DELIMITER) {
                    break;
                }

                $this->body[] = $token;
            }
        }
    }
}
