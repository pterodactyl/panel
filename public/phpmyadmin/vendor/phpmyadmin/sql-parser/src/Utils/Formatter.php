<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Components\JoinKeyword;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function array_merge;
use function array_pop;
use function end;
use function htmlspecialchars;
use function in_array;
use function mb_strlen;
use function str_contains;
use function str_repeat;
use function str_replace;
use function strtoupper;

use const ENT_NOQUOTES;
use const PHP_SAPI;

/**
 * Utilities that are used for formatting queries.
 */
class Formatter
{
    /**
     * The formatting options.
     *
     * @var array<string, bool|string|array<int, array<string, int|string>>>
     */
    public $options;

    /**
     * Clauses that are usually short.
     *
     * These clauses share the line with the next clause.
     *
     * E.g. if INSERT was not here, the formatter would produce:
     *
     *      INSERT
     *      INTO foo
     *      VALUES(0, 0, 0),(1, 1, 1);
     *
     * Instead of:
     *
     *      INSERT INTO foo
     *      VALUES(0, 0, 0),(1, 1, 1)
     *
     * @var array<string, bool>
     */
    public static $SHORT_CLAUSES = [
        'CREATE' => true,
        'INSERT' => true,
    ];

    /**
     * Clauses that must be inlined.
     *
     * These clauses usually are short and it's nicer to have them inline.
     *
     * @var array<string, bool>
     */
    public static $INLINE_CLAUSES = [
        'CREATE' => true,
        'INTO' => true,
        'LIMIT' => true,
        'PARTITION BY' => true,
        'PARTITION' => true,
        'PROCEDURE' => true,
        'SUBPARTITION BY' => true,
        'VALUES' => true,
    ];

    /**
     * @param array<string, bool|string|array<int, array<string, int|string>>> $options the formatting options
     */
    public function __construct(array $options = [])
    {
        $this->options = $this->getMergedOptions($options);
    }

    /**
     * The specified formatting options are merged with the default values.
     *
     * @param array<string, bool|string|array<int, array<string, int|string>>> $options
     *
     * @return array<string, bool|string|array<int, array<string, int|string>>>
     */
    protected function getMergedOptions(array $options)
    {
        $options = array_merge(
            $this->getDefaultOptions(),
            $options
        );

        if (isset($options['formats'])) {
            $options['formats'] = self::mergeFormats($this->getDefaultFormats(), $options['formats']);
        } else {
            $options['formats'] = $this->getDefaultFormats();
        }

        if ($options['line_ending'] === null) {
            $options['line_ending'] = $options['type'] === 'html' ? '<br/>' : "\n";
        }

        if ($options['indentation'] === null) {
            $options['indentation'] = $options['type'] === 'html' ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '    ';
        }

        // `parts_newline` requires `clause_newline`
        $options['parts_newline'] &= $options['clause_newline'];

        return $options;
    }

    /**
     * The default formatting options.
     *
     * @return array<string, bool|string|null>
     * @psalm-return array{
     *   type: ('cli'|'text'),
     *   line_ending: null,
     *   indentation: null,
     *   remove_comments: false,
     *   clause_newline: true,
     *   parts_newline: true,
     *   indent_parts: true
     * }
     */
    protected function getDefaultOptions()
    {
        return [
            /*
             * The format of the result.
             *
             * @var string The type ('text', 'cli' or 'html')
             */
            'type' => PHP_SAPI === 'cli' ? 'cli' : 'text',

            /*
             * The line ending used.
             * By default, for text this is "\n" and for HTML this is "<br/>".
             *
             * @var string
             */
            'line_ending' => null,

            /*
             * The string used for indentation.
             *
             * @var string
             */
            'indentation' => null,

            /*
             * Whether comments should be removed or not.
             *
             * @var bool
             */
            'remove_comments' => false,

            /*
             * Whether each clause should be on a new line.
             *
             * @var bool
             */
            'clause_newline' => true,

            /*
             * Whether each part should be on a new line.
             * Parts are delimited by brackets and commas.
             *
             * @var bool
             */
            'parts_newline' => true,

            /*
             * Whether each part of each clause should be indented.
             *
             * @var bool
             */
            'indent_parts' => true,
        ];
    }

    /**
     * The styles used for HTML formatting.
     * [$type, $flags, $span, $callback].
     *
     * @return array<int, array<string, int|string>>
     * @psalm-return list<array{type: int, flags: int, html: string, cli: string, function: string}>
     */
    protected function getDefaultFormats()
    {
        return [
            [
                'type' => Token::TYPE_KEYWORD,
                'flags' => Token::FLAG_KEYWORD_RESERVED,
                'html' => 'class="sql-reserved"',
                'cli' => "\x1b[35m",
                'function' => 'strtoupper',
            ],
            [
                'type' => Token::TYPE_KEYWORD,
                'flags' => 0,
                'html' => 'class="sql-keyword"',
                'cli' => "\x1b[95m",
                'function' => 'strtoupper',
            ],
            [
                'type' => Token::TYPE_COMMENT,
                'flags' => 0,
                'html' => 'class="sql-comment"',
                'cli' => "\x1b[37m",
                'function' => '',
            ],
            [
                'type' => Token::TYPE_BOOL,
                'flags' => 0,
                'html' => 'class="sql-atom"',
                'cli' => "\x1b[36m",
                'function' => 'strtoupper',
            ],
            [
                'type' => Token::TYPE_NUMBER,
                'flags' => 0,
                'html' => 'class="sql-number"',
                'cli' => "\x1b[92m",
                'function' => 'strtolower',
            ],
            [
                'type' => Token::TYPE_STRING,
                'flags' => 0,
                'html' => 'class="sql-string"',
                'cli' => "\x1b[91m",
                'function' => '',
            ],
            [
                'type' => Token::TYPE_SYMBOL,
                'flags' => Token::FLAG_SYMBOL_PARAMETER,
                'html' => 'class="sql-parameter"',
                'cli' => "\x1b[31m",
                'function' => '',
            ],
            [
                'type' => Token::TYPE_SYMBOL,
                'flags' => 0,
                'html' => 'class="sql-variable"',
                'cli' => "\x1b[36m",
                'function' => '',
            ],
        ];
    }

    /**
     * @param array<int, array<string, int|string>> $formats
     * @param array<int, array<string, int|string>> $newFormats
     *
     * @return array<int, array<string, int|string>>
     */
    private static function mergeFormats(array $formats, array $newFormats): array
    {
        $added = [];
        $integers = [
            'flags',
            'type',
        ];
        $strings = [
            'html',
            'cli',
            'function',
        ];

        /* Sanitize the array so that we do not have to care later */
        foreach ($newFormats as $j => $new) {
            foreach ($integers as $name) {
                if (isset($new[$name])) {
                    continue;
                }

                $newFormats[$j][$name] = 0;
            }

            foreach ($strings as $name) {
                if (isset($new[$name])) {
                    continue;
                }

                $newFormats[$j][$name] = '';
            }
        }

        /* Process changes to existing formats */
        foreach ($formats as $i => $original) {
            foreach ($newFormats as $j => $new) {
                if ($new['type'] !== $original['type'] || $original['flags'] !== $new['flags']) {
                    continue;
                }

                $formats[$i] = $new;
                $added[] = $j;
            }
        }

        /* Add not already handled formats */
        foreach ($newFormats as $j => $new) {
            if (in_array($j, $added)) {
                continue;
            }

            $formats[] = $new;
        }

        return $formats;
    }

    /**
     * Formats the given list of tokens.
     *
     * @param TokensList $list the list of tokens
     *
     * @return string
     */
    public function formatList($list)
    {
        /**
         * The query to be returned.
         *
         * @var string
         */
        $ret = '';

        /**
         * The indentation level.
         *
         * @var int
         */
        $indent = 0;

        /**
         * Whether the line ended.
         *
         * @var bool
         */
        $lineEnded = false;

        /**
         * Whether current group is short (no linebreaks).
         *
         * @var bool
         */
        $shortGroup = false;

        /**
         * The name of the last clause.
         *
         * @var string
         */
        $lastClause = '';

        /**
         * A stack that keeps track of the indentation level every time a new
         * block is found.
         */
        $blocksIndentation = [];

        /**
         * A stack that keeps track of the line endings every time a new block
         * is found.
         */
        $blocksLineEndings = [];

        /**
         * Whether clause's options were formatted.
         *
         * @var bool
         */
        $formattedOptions = false;

        /**
         * Previously parsed token.
         *
         * @var Token|null
         */
        $prev = null;

        // In order to be able to format the queries correctly, the next token
        // must be taken into consideration. The loop below uses two pointers,
        // `$prev` and `$curr` which store two consecutive tokens.
        // Actually, at every iteration the previous token is being used.
        for ($list->idx = 0; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $curr = $list->tokens[$list->idx];
            if ($list->idx + 1 < $list->count) {
                $next = $list->tokens[$list->idx + 1];
            } else {
                $next = null;
            }

            if ($curr->type === Token::TYPE_WHITESPACE) {
                // Keep linebreaks before and after comments
                if (
                    str_contains($curr->token, "\n") && (
                        ($prev !== null && $prev->type === Token::TYPE_COMMENT) ||
                        ($next !== null && $next->type === Token::TYPE_COMMENT)
                    )
                ) {
                    $lineEnded = true;
                }

                // Whitespaces are skipped because the formatter adds its own.
                continue;
            }

            if ($curr->type === Token::TYPE_COMMENT && $this->options['remove_comments']) {
                // Skip Comments if option `remove_comments` is enabled
                continue;
            }

            // Checking if pointers were initialized.
            if ($prev !== null) {
                // Checking if a new clause started.
                if (static::isClause($prev) !== false) {
                    $lastClause = $prev->value;
                    $formattedOptions = false;
                }

                // The options of a clause should stay on the same line and everything that follows.
                if (
                    $this->options['parts_newline']
                    && ! $formattedOptions
                    && empty(self::$INLINE_CLAUSES[$lastClause])
                    && (
                        $curr->type !== Token::TYPE_KEYWORD
                        || (
                            $curr->type === Token::TYPE_KEYWORD
                            && $curr->flags & Token::FLAG_KEYWORD_FUNCTION
                        )
                    )
                ) {
                    $formattedOptions = true;
                    $lineEnded = true;
                    ++$indent;
                }

                // Checking if this clause ended.
                $isClause = static::isClause($curr);

                if ($isClause !== false) {
                    if (
                        ($isClause === 2 || $this->options['clause_newline'])
                        && empty(self::$SHORT_CLAUSES[$lastClause])
                    ) {
                        $lineEnded = true;
                        if ($this->options['parts_newline'] && $indent > 0) {
                            --$indent;
                        }
                    }
                }

                // Inline JOINs
                if (
                    ($prev->type === Token::TYPE_KEYWORD && isset(JoinKeyword::$JOINS[$prev->value]))
                    || (in_array($curr->value, ['ON', 'USING'], true)
                        && isset(JoinKeyword::$JOINS[$list->tokens[$list->idx - 2]->value]))
                    || isset($list->tokens[$list->idx - 4], JoinKeyword::$JOINS[$list->tokens[$list->idx - 4]->value])
                    || isset($list->tokens[$list->idx - 6], JoinKeyword::$JOINS[$list->tokens[$list->idx - 6]->value])
                ) {
                    $lineEnded = false;
                }

                // Indenting BEGIN ... END blocks.
                if ($prev->type === Token::TYPE_KEYWORD && $prev->keyword === 'BEGIN') {
                    $lineEnded = true;
                    $blocksIndentation[] = $indent;
                    ++$indent;
                } elseif ($curr->type === Token::TYPE_KEYWORD && $curr->keyword === 'END') {
                    $lineEnded = true;
                    $indent = array_pop($blocksIndentation);
                }

                // Formatting fragments delimited by comma.
                if ($prev->type === Token::TYPE_OPERATOR && $prev->value === ',') {
                    // Fragments delimited by a comma are broken into multiple
                    // pieces only if the clause is not inlined or this fragment
                    // is between brackets that are on new line.
                    if (
                        end($blocksLineEndings) === true
                        || (
                            empty(self::$INLINE_CLAUSES[$lastClause])
                            && ! $shortGroup
                            && $this->options['parts_newline']
                        )
                    ) {
                        $lineEnded = true;
                    }
                }

                // Handling brackets.
                // Brackets are indented only if the length of the fragment between
                // them is longer than 30 characters.
                if ($prev->type === Token::TYPE_OPERATOR && $prev->value === '(') {
                    $blocksIndentation[] = $indent;
                    $shortGroup = true;
                    if (static::getGroupLength($list) > 30) {
                        ++$indent;
                        $lineEnded = true;
                        $shortGroup = false;
                    }

                    $blocksLineEndings[] = $lineEnded;
                } elseif ($curr->type === Token::TYPE_OPERATOR && $curr->value === ')') {
                    $indent = array_pop($blocksIndentation);
                    $lineEnded |= array_pop($blocksLineEndings);
                    $shortGroup = false;
                }

                // Adding the token.
                $ret .= $this->toString($prev);

                // Finishing the line.
                if ($lineEnded) {
                    $ret .= $this->options['line_ending']
                        . str_repeat($this->options['indentation'], (int) $indent);

                    $lineEnded = false;
                } else {
                    // If the line ended there is no point in adding whitespaces.
                    // Also, some tokens do not have spaces before or after them.
                    if (
                        // A space after delimiters that are longer than 2 characters.
                        $prev->keyword === 'DELIMITER'
                        || ! (
                            ($prev->type === Token::TYPE_OPERATOR && ($prev->value === '.' || $prev->value === '('))
                            // No space after . (
                            || ($curr->type === Token::TYPE_OPERATOR
                                && ($curr->value === '.' || $curr->value === ','
                                    || $curr->value === '(' || $curr->value === ')'))
                            // No space before . , ( )
                            || $curr->type === Token::TYPE_DELIMITER && mb_strlen((string) $curr->value, 'UTF-8') < 2
                        )
                    ) {
                        $ret .= ' ';
                    }
                }
            }

            // Iteration finished, consider current token as previous.
            $prev = $curr;
        }

        if ($this->options['type'] === 'cli') {
            return $ret . "\x1b[0m";
        }

        return $ret;
    }

    public function escapeConsole(string $string): string
    {
        return str_replace(
            [
                "\x00",
                "\x01",
                "\x02",
                "\x03",
                "\x04",
                "\x05",
                "\x06",
                "\x07",
                "\x08",
                "\x09",
                "\x0A",
                "\x0B",
                "\x0C",
                "\x0D",
                "\x0E",
                "\x0F",
                "\x10",
                "\x11",
                "\x12",
                "\x13",
                "\x14",
                "\x15",
                "\x16",
                "\x17",
                "\x18",
                "\x19",
                "\x1A",
                "\x1B",
                "\x1C",
                "\x1D",
                "\x1E",
                "\x1F",
            ],
            [
                '\x00',
                '\x01',
                '\x02',
                '\x03',
                '\x04',
                '\x05',
                '\x06',
                '\x07',
                '\x08',
                '\x09',
                '\x0A',
                '\x0B',
                '\x0C',
                '\x0D',
                '\x0E',
                '\x0F',
                '\x10',
                '\x11',
                '\x12',
                '\x13',
                '\x14',
                '\x15',
                '\x16',
                '\x17',
                '\x18',
                '\x19',
                '\x1A',
                '\x1B',
                '\x1C',
                '\x1D',
                '\x1E',
                '\x1F',
            ],
            $string
        );
    }

    /**
     * Tries to print the query and returns the result.
     *
     * @param Token $token the token to be printed
     *
     * @return string
     */
    public function toString($token)
    {
        $text = $token->token;
        static $prev;

        foreach ($this->options['formats'] as $format) {
            if ($token->type !== $format['type'] || ! (($token->flags & $format['flags']) === $format['flags'])) {
                continue;
            }

            // Running transformation function.
            if (! empty($format['function'])) {
                $func = $format['function'];
                $text = $func($text);
            }

            // Formatting HTML.
            if ($this->options['type'] === 'html') {
                return '<span ' . $format['html'] . '>' . htmlspecialchars($text, ENT_NOQUOTES) . '</span>';
            }

            if ($this->options['type'] === 'cli') {
                if ($prev !== $format['cli']) {
                    $prev = $format['cli'];

                    return $format['cli'] . $this->escapeConsole($text);
                }

                return $this->escapeConsole($text);
            }

            break;
        }

        if ($this->options['type'] === 'cli') {
            if ($prev !== "\x1b[39m") {
                $prev = "\x1b[39m";

                return "\x1b[39m" . $this->escapeConsole($text);
            }

            return $this->escapeConsole($text);
        }

        if ($this->options['type'] === 'html') {
            return htmlspecialchars($text, ENT_NOQUOTES);
        }

        return $text;
    }

    /**
     * Formats a query.
     *
     * @param string                                                           $query   The query to be formatted
     * @param array<string, bool|string|array<int, array<string, int|string>>> $options the formatting options
     *
     * @return string the formatted string
     */
    public static function format($query, array $options = [])
    {
        $lexer = new Lexer($query);
        $formatter = new self($options);

        return $formatter->formatList($lexer->list);
    }

    /**
     * Computes the length of a group.
     *
     * A group is delimited by a pair of brackets.
     *
     * @param TokensList $list the list of tokens
     *
     * @return int
     */
    public static function getGroupLength($list)
    {
        /**
         * The number of opening brackets found.
         * This counter starts at one because by the time this function called,
         * the list already advanced one position and the opening bracket was
         * already parsed.
         *
         * @var int
         */
        $count = 1;

        /**
         * The length of this group.
         *
         * @var int
         */
        $length = 0;

        for ($idx = $list->idx; $idx < $list->count; ++$idx) {
            // Counting the brackets.
            if ($list->tokens[$idx]->type === Token::TYPE_OPERATOR) {
                if ($list->tokens[$idx]->value === '(') {
                    ++$count;
                } elseif ($list->tokens[$idx]->value === ')') {
                    --$count;
                    if ($count === 0) {
                        break;
                    }
                }
            }

            // Keeping track of this group's length.
            $length += mb_strlen((string) $list->tokens[$idx]->value, 'UTF-8');
        }

        return $length;
    }

    /**
     * Checks if a token is a statement or a clause inside a statement.
     *
     * @param Token $token the token to be checked
     *
     * @return int|false
     * @psalm-return 1|2|false
     */
    public static function isClause($token)
    {
        if (
            ($token->type === Token::TYPE_KEYWORD && isset(Parser::$STATEMENT_PARSERS[$token->keyword]))
            || ($token->type === Token::TYPE_NONE && strtoupper($token->token) === 'DELIMITER')
        ) {
            return 2;
        }

        if ($token->type === Token::TYPE_KEYWORD && isset(Parser::$KEYWORD_PARSERS[$token->keyword])) {
            return 1;
        }

        return false;
    }
}
