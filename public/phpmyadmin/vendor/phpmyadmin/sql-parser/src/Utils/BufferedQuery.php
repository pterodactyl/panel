<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Context;

use function array_merge;
use function strlen;
use function substr;
use function trim;

/**
 * Buffer query utilities.
 *
 * Implements a specialized lexer used to extract statements from large inputs
 * that are being buffered. After each statement has been extracted, a lexer or
 * a parser may be used.
 */
class BufferedQuery
{
    // Constants that describe the current status of the parser.

    // A string is being parsed.
    public const STATUS_STRING = 16; // 0001 0000
    public const STATUS_STRING_SINGLE_QUOTES = 17; // 0001 0001
    public const STATUS_STRING_DOUBLE_QUOTES = 18; // 0001 0010
    public const STATUS_STRING_BACKTICK = 20; // 0001 0100

    // A comment is being parsed.
    public const STATUS_COMMENT = 32; // 0010 0000
    public const STATUS_COMMENT_BASH = 33; // 0010 0001
    public const STATUS_COMMENT_C = 34; // 0010 0010
    public const STATUS_COMMENT_SQL = 36; // 0010 0100

    /**
     * The query that is being processed.
     *
     * This field can be modified just by appending to it!
     *
     * @var string
     */
    public $query = '';

    /**
     * The options of this parser.
     *
     * @var array<string, bool|string>
     * @psalm-var array{delimiter?: non-empty-string, parse_delimiter?: bool, add_delimiter?: bool}
     */
    public $options = [];

    /**
     * The last delimiter used.
     *
     * @var string
     */
    public $delimiter;

    /**
     * The length of the delimiter.
     *
     * @var int
     */
    public $delimiterLen;

    /**
     * The current status of the parser.
     *
     * @var int|null
     */
    public $status;

    /**
     * The last incomplete query that was extracted.
     *
     * @var string
     */
    public $current = '';

    /**
     * @param string                     $query   the query to be parsed
     * @param array<string, bool|string> $options the options of this parser
     * @psalm-param array{delimiter?: non-empty-string, parse_delimiter?: bool, add_delimiter?: bool} $options
     */
    public function __construct($query = '', array $options = [])
    {
        // Merges specified options with defaults.
        $this->options = array_merge(
            [
                // The starting delimiter.
                'delimiter' => ';',
                // Whether `DELIMITER` statements should be parsed.
                'parse_delimiter' => false,
                // Whether a delimiter should be added at the end of the statement.
                'add_delimiter' => false,
            ],
            $options
        );

        $this->query = $query;
        $this->setDelimiter($this->options['delimiter']);
    }

    /**
     * Sets the delimiter.
     *
     * Used to update the length of it too.
     *
     * @param string $delimiter
     *
     * @return void
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        $this->delimiterLen = strlen($delimiter);
    }

    /**
     * Extracts a statement from the buffer.
     *
     * @param bool $end whether the end of the buffer was reached
     *
     * @return string|false
     */
    public function extract($end = false)
    {
        /**
         * The last parsed position.
         *
         * This is statically defined because it is not used outside anywhere
         * outside this method and there is probably a (minor) performance
         * improvement to it.
         *
         * @var int
         */
        static $i = 0;

        if (empty($this->query)) {
            return false;
        }

        /**
         * The length of the buffer.
         *
         * @var int
         */
        $len = strlen($this->query);

        /**
         * The last index of the string that is going to be parsed.
         *
         * There must be a few characters left in the buffer so the parser can
         * avoid confusing some symbols that may have multiple meanings.
         *
         * For example, if the buffer ends in `-` that may be an operator or the
         * beginning of a comment.
         *
         * Another example if the buffer ends in `DELIMITE`. The parser is going
         * to require a few more characters because that may be a part of the
         * `DELIMITER` keyword or just a column named `DELIMITE`.
         *
         * Those extra characters are required only if there is more data
         * expected (the end of the buffer was not reached).
         */
        $loopLen = $end ? $len : $len - 16;

        for (; $i < $loopLen; ++$i) {
            /*
             * Handling backslash.
             *
             * Even if the next character is a special character that should be
             * treated differently, because of the preceding backslash, it will
             * be ignored.
             */
            if ((($this->status & self::STATUS_COMMENT) === 0) && ($this->query[$i] === '\\')) {
                $this->current .= $this->query[$i] . ($i + 1 < $len ? $this->query[++$i] : '');
                continue;
            }

            /*
             * Handling special parses statuses.
             */
            if ($this->status === self::STATUS_STRING_SINGLE_QUOTES) {
                // Single-quoted strings like 'foo'.
                if ($this->query[$i] === '\'') {
                    $this->status = 0;
                }

                $this->current .= $this->query[$i];
                continue;
            } elseif ($this->status === self::STATUS_STRING_DOUBLE_QUOTES) {
                // Double-quoted strings like "bar".
                if ($this->query[$i] === '"') {
                    $this->status = 0;
                }

                $this->current .= $this->query[$i];
                continue;
            } elseif ($this->status === self::STATUS_STRING_BACKTICK) {
                if ($this->query[$i] === '`') {
                    $this->status = 0;
                }

                $this->current .= $this->query[$i];
                continue;
            } elseif (($this->status === self::STATUS_COMMENT_BASH) || ($this->status === self::STATUS_COMMENT_SQL)) {
                // Bash-like (#) or SQL-like (-- ) comments end in new line.
                if ($this->query[$i] === "\n") {
                    $this->status = 0;
                }

                $this->current .= $this->query[$i];
                continue;
            } elseif ($this->status === self::STATUS_COMMENT_C) {
                // C-like comments end in */.
                if (($this->query[$i - 1] === '*') && ($this->query[$i] === '/')) {
                    $this->status = 0;
                }

                $this->current .= $this->query[$i];
                continue;
            }

            /*
             * Checking if a string started.
             */
            if ($this->query[$i] === '\'') {
                $this->status = self::STATUS_STRING_SINGLE_QUOTES;
                $this->current .= $this->query[$i];
                continue;
            }

            if ($this->query[$i] === '"') {
                $this->status = self::STATUS_STRING_DOUBLE_QUOTES;
                $this->current .= $this->query[$i];
                continue;
            }

            if ($this->query[$i] === '`') {
                $this->status = self::STATUS_STRING_BACKTICK;
                $this->current .= $this->query[$i];
                continue;
            }

            /*
             * Checking if a comment started.
             */
            if ($this->query[$i] === '#') {
                $this->status = self::STATUS_COMMENT_BASH;
                $this->current .= $this->query[$i];
                continue;
            }

            if ($i + 2 < $len) {
                if (
                    ($this->query[$i] === '-')
                    && ($this->query[$i + 1] === '-')
                    && Context::isWhitespace($this->query[$i + 2])
                ) {
                    $this->status = self::STATUS_COMMENT_SQL;
                    $this->current .= $this->query[$i];
                    continue;
                }

                if (($this->query[$i] === '/') && ($this->query[$i + 1] === '*') && ($this->query[$i + 2] !== '!')) {
                    $this->status = self::STATUS_COMMENT_C;
                    $this->current .= $this->query[$i];
                    continue;
                }
            }

            /*
             * Handling `DELIMITER` statement.
             *
             * The code below basically checks for
             *     `strtoupper(substr($this->query, $i, 9)) === 'DELIMITER'`
             *
             * This optimization makes the code about 3 times faster.
             *
             * `DELIMITER` is not being considered a keyword. The only context
             * it has a special meaning is when it is the beginning of a
             * statement. This is the reason for the last condition.
             */
            if (
                ($i + 9 < $len)
                && (($this->query[$i] === 'D') || ($this->query[$i] === 'd'))
                && (($this->query[$i + 1] === 'E') || ($this->query[$i + 1] === 'e'))
                && (($this->query[$i + 2] === 'L') || ($this->query[$i + 2] === 'l'))
                && (($this->query[$i + 3] === 'I') || ($this->query[$i + 3] === 'i'))
                && (($this->query[$i + 4] === 'M') || ($this->query[$i + 4] === 'm'))
                && (($this->query[$i + 5] === 'I') || ($this->query[$i + 5] === 'i'))
                && (($this->query[$i + 6] === 'T') || ($this->query[$i + 6] === 't'))
                && (($this->query[$i + 7] === 'E') || ($this->query[$i + 7] === 'e'))
                && (($this->query[$i + 8] === 'R') || ($this->query[$i + 8] === 'r'))
                && Context::isWhitespace($this->query[$i + 9])
            ) {
                // Saving the current index to be able to revert any parsing
                // done in this block.
                $iBak = $i;
                $i += 9; // Skipping `DELIMITER`.

                // Skipping whitespaces.
                while (($i < $len) && Context::isWhitespace($this->query[$i])) {
                    ++$i;
                }

                // Parsing the delimiter.
                $delimiter = '';
                while (($i < $len) && (! Context::isWhitespace($this->query[$i]))) {
                    $delimiter .= $this->query[$i++];
                }

                // Checking if the delimiter definition ended.
                if (
                    ($delimiter !== '')
                    && (($i < $len) && Context::isWhitespace($this->query[$i])
                    || (($i === $len) && $end))
                ) {
                    // Saving the delimiter.
                    $this->setDelimiter($delimiter);

                    // Whether this statement should be returned or not.
                    $ret = '';
                    if (! empty($this->options['parse_delimiter'])) {
                        // Appending the `DELIMITER` statement that was just
                        // found to the current statement.
                        $ret = trim(
                            $this->current . ' ' . substr($this->query, $iBak, $i - $iBak)
                        );
                    }

                    // Removing the statement that was just extracted from the
                    // query.
                    $this->query = substr($this->query, $i);
                    $i = 0;

                    // Resetting the current statement.
                    $this->current = '';

                    return $ret;
                }

                // Incomplete statement. Reverting
                $i = $iBak;

                return false;
            }

            /*
             * Checking if the current statement finished.
             *
             * The first letter of the delimiter is being checked as an
             * optimization. This code is almost as fast as the one above.
             *
             * There is no point in checking if two strings match if not even
             * the first letter matches.
             */
            if (
                ($this->query[$i] === $this->delimiter[0])
                && (($this->delimiterLen === 1)
                || (substr($this->query, $i, $this->delimiterLen) === $this->delimiter))
            ) {
                // Saving the statement that just ended.
                $ret = $this->current;

                // If needed, adds a delimiter at the end of the statement.
                if (! empty($this->options['add_delimiter'])) {
                    $ret .= $this->delimiter;
                }

                // Removing the statement that was just extracted from the
                // query.
                $this->query = substr($this->query, $i + $this->delimiterLen);
                $i = 0;

                // Resetting the current statement.
                $this->current = '';

                // Returning the statement.
                return trim($ret);
            }

            /*
             * Appending current character to current statement.
             */
            $this->current .= $this->query[$i];
        }

        if ($end && ($i === $len)) {
            // If the end of the buffer was reached, the buffer is emptied and
            // the current statement that was extracted is returned.
            $ret = $this->current;

            // Emptying the buffer.
            $this->query = '';
            $i = 0;

            // Resetting the current statement.
            $this->current = '';

            // Returning the statement.
            return trim($ret);
        }

        return '';
    }
}
