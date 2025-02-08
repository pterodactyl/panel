<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use function hexdec;
use function mb_strlen;
use function mb_substr;
use function str_replace;
use function stripcslashes;
use function strtoupper;

/**
 * Defines a token along with a set of types and flags and utility functions.
 *
 * An array of tokens will result after parsing the query.
 *
 * A structure representing a lexeme that explicitly indicates its categorization for the purpose of parsing.
 */
class Token
{
    // Types of tokens (a vague description of a token's purpose).

    /**
     * This type is used when the token is invalid or its type cannot be
     * determined because of the ambiguous context. Further analysis might be
     * required to detect its type.
     */
    public const TYPE_NONE = 0;

    /**
     * SQL specific keywords: SELECT, UPDATE, INSERT, etc.
     */
    public const TYPE_KEYWORD = 1;

    /**
     * Any type of legal operator.
     *
     * Arithmetic operators: +, -, *, /, etc.
     * Logical operators: ===, <>, !==, etc.
     * Bitwise operators: &, |, ^, etc.
     * Assignment operators: =, +=, -=, etc.
     * SQL specific operators: . (e.g. .. WHERE database.table ..),
     *                         * (e.g. SELECT * FROM ..)
     */
    public const TYPE_OPERATOR = 2;

    /**
     * Spaces, tabs, new lines, etc.
     */
    public const TYPE_WHITESPACE = 3;

    /**
     * Any type of legal comment.
     *
     * Bash (#), C (/* *\/) or SQL (--) comments:
     *
     *      -- SQL-comment
     *
     *      #Bash-like comment
     *
     *      /*C-like comment*\/
     *
     * or:
     *
     *      /*C-like
     *        comment*\/
     *
     * Backslashes were added to respect PHP's comments syntax.
     */
    public const TYPE_COMMENT = 4;

    /**
     * Boolean values: true or false.
     */
    public const TYPE_BOOL = 5;

    /**
     * Numbers: 4, 0x8, 15.16, 23e42, etc.
     */
    public const TYPE_NUMBER = 6;

    /**
     * Literal strings: 'string', "test".
     * Some of these strings are actually symbols.
     */
    public const TYPE_STRING = 7;

    /**
     * Database, table names, variables, etc.
     * For example: ```SELECT `foo`, `bar` FROM `database`.`table`;```.
     */
    public const TYPE_SYMBOL = 8;

    /**
     * Delimits an unknown string.
     * For example: ```SELECT * FROM test;```, `test` is a delimiter.
     */
    public const TYPE_DELIMITER = 9;

    /**
     * Labels in LOOP statement, ITERATE statement etc.
     * For example (only for begin label):
     *  begin_label: BEGIN [statement_list] END [end_label]
     *  begin_label: LOOP [statement_list] END LOOP [end_label]
     *  begin_label: REPEAT [statement_list] ... END REPEAT [end_label]
     *  begin_label: WHILE ... DO [statement_list] END WHILE [end_label].
     */
    public const TYPE_LABEL = 10;

    /**
     *  All tokens types
     */
    public const TYPE_ALL = [
        self::TYPE_NONE,
        self::TYPE_KEYWORD,
        self::TYPE_OPERATOR,
        self::TYPE_WHITESPACE,
        self::TYPE_COMMENT,
        self::TYPE_BOOL,
        self::TYPE_NUMBER,
        self::TYPE_STRING,
        self::TYPE_SYMBOL,
        self::TYPE_DELIMITER,
        self::TYPE_LABEL,
    ];

    // Flags that describe the tokens in more detail.
    // All keywords must have flag 1 so `Context::isKeyword` method doesn't
    // require strict comparison.
    public const FLAG_KEYWORD = 1;
    public const FLAG_KEYWORD_RESERVED = 2;
    public const FLAG_KEYWORD_COMPOSED = 4;
    public const FLAG_KEYWORD_DATA_TYPE = 8;
    public const FLAG_KEYWORD_KEY = 16;
    public const FLAG_KEYWORD_FUNCTION = 32;

    // Numbers related flags.
    public const FLAG_NUMBER_HEX = 1;
    public const FLAG_NUMBER_FLOAT = 2;
    public const FLAG_NUMBER_APPROXIMATE = 4;
    public const FLAG_NUMBER_NEGATIVE = 8;
    public const FLAG_NUMBER_BINARY = 16;

    // Strings related flags.
    public const FLAG_STRING_SINGLE_QUOTES = 1;
    public const FLAG_STRING_DOUBLE_QUOTES = 2;

    // Comments related flags.
    public const FLAG_COMMENT_BASH = 1;
    public const FLAG_COMMENT_C = 2;
    public const FLAG_COMMENT_SQL = 4;
    public const FLAG_COMMENT_MYSQL_CMD = 8;

    // Operators related flags.
    public const FLAG_OPERATOR_ARITHMETIC = 1;
    public const FLAG_OPERATOR_LOGICAL = 2;
    public const FLAG_OPERATOR_BITWISE = 4;
    public const FLAG_OPERATOR_ASSIGNMENT = 8;
    public const FLAG_OPERATOR_SQL = 16;

    // Symbols related flags.
    public const FLAG_SYMBOL_VARIABLE = 1;
    public const FLAG_SYMBOL_BACKTICK = 2;
    public const FLAG_SYMBOL_USER = 4;
    public const FLAG_SYMBOL_SYSTEM = 8;
    public const FLAG_SYMBOL_PARAMETER = 16;

    /**
     * The token it its raw string representation.
     *
     * @var string
     */
    public $token;

    /**
     * The value this token contains (i.e. token after some evaluation).
     *
     * @var mixed
     */
    public $value;

    /**
     * The keyword value this token contains, always uppercase.
     *
     * @var mixed|string|null
     */
    public $keyword;

    /**
     * The type of this token.
     *
     * @var int
     */
    public $type;

    /**
     * The flags of this token.
     *
     * @var int
     */
    public $flags;

    /**
     * The position in the initial string where this token started.
     *
     * The position is counted in chars, not bytes, so you should
     * use mb_* functions to properly handle utf-8 multibyte chars.
     *
     * @var int|null
     */
    public $position;

    /**
     * @param string $token the value of the token
     * @param int    $type  the type of the token
     * @param int    $flags the flags of the token
     */
    public function __construct($token, $type = 0, $flags = 0)
    {
        $this->token = $token;
        $this->type = $type;
        $this->flags = $flags;
        $this->keyword = null;
        $this->value = $this->extract();
    }

    /**
     * Does little processing to the token to extract a value.
     *
     * If no processing can be done it will return the initial string.
     *
     * @return mixed
     */
    public function extract()
    {
        switch ($this->type) {
            case self::TYPE_KEYWORD:
                $this->keyword = strtoupper($this->token);
                if (! ($this->flags & self::FLAG_KEYWORD_RESERVED)) {
                    // Unreserved keywords should stay the way they are because they
                    // might represent field names.
                    return $this->token;
                }

                return $this->keyword;

            case self::TYPE_WHITESPACE:
                return ' ';

            case self::TYPE_BOOL:
                return strtoupper($this->token) === 'TRUE';

            case self::TYPE_NUMBER:
                $ret = str_replace('--', '', $this->token); // e.g. ---42 === -42
                if ($this->flags & self::FLAG_NUMBER_HEX) {
                    $ret = str_replace(['-', '+'], '', $this->token);
                    if ($this->flags & self::FLAG_NUMBER_NEGATIVE) {
                        $ret = -hexdec($ret);
                    } else {
                        $ret = hexdec($ret);
                    }
                } elseif (($this->flags & self::FLAG_NUMBER_APPROXIMATE) || ($this->flags & self::FLAG_NUMBER_FLOAT)) {
                    $ret = (float) $ret;
                } elseif (! ($this->flags & self::FLAG_NUMBER_BINARY)) {
                    $ret = (int) $ret;
                }

                return $ret;

            case self::TYPE_STRING:
                // Trims quotes.
                $str = $this->token;
                $str = mb_substr($str, 1, -1, 'UTF-8');

                // Removes surrounding quotes.
                $quote = $this->token[0];
                $str = str_replace($quote . $quote, $quote, $str);

                // Finally unescapes the string.
                //
                // `stripcslashes` replaces escape sequences with their
                // representation.
                //
                // NOTE: In MySQL, `\f` and `\v` have no representation,
                // even they usually represent: form-feed and vertical tab.
                $str = str_replace('\f', 'f', $str);
                $str = str_replace('\v', 'v', $str);
                $str = stripcslashes($str);

                return $str;

            case self::TYPE_SYMBOL:
                $str = $this->token;
                if (isset($str[0]) && ($str[0] === '@')) {
                    // `mb_strlen($str)` must be used instead of `null` because
                    // in PHP 5.3- the `null` parameter isn't handled correctly.
                    $str = mb_substr(
                        $str,
                        ! empty($str[1]) && ($str[1] === '@') ? 2 : 1,
                        mb_strlen($str),
                        'UTF-8'
                    );
                }

                if (isset($str[0]) && ($str[0] === ':')) {
                    $str = mb_substr($str, 1, mb_strlen($str), 'UTF-8');
                }

                if (isset($str[0]) && (($str[0] === '`') || ($str[0] === '"') || ($str[0] === '\''))) {
                    $quote = $str[0];
                    $str = mb_substr($str, 1, -1, 'UTF-8');
                    $str = str_replace($quote . $quote, $quote, $str);
                }

                return $str;
        }

        return $this->token;
    }

    /**
     * Converts the token into an inline token by replacing tabs and new lines.
     *
     * @return string
     */
    public function getInlineToken()
    {
        return str_replace(
            [
                "\r",
                "\n",
                "\t",
            ],
            [
                '\r',
                '\n',
                '\t',
            ],
            $this->token
        );
    }
}
