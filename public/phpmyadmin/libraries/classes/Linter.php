<?php
/**
 * Analyzes a query and gives user feedback.
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\UtfString;
use PhpMyAdmin\SqlParser\Utils\Error as ParserError;

use function __;
use function defined;
use function htmlspecialchars;
use function mb_strlen;
use function sprintf;
use function strlen;

/**
 * The linter itself.
 */
class Linter
{
    /**
     * Gets the starting position of each line.
     *
     * @param string|UtfString $str String to be analyzed.
     *
     * @return array<int,int>
     * @psalm-return list<int>
     */
    public static function getLines($str)
    {
        if ((! ($str instanceof UtfString)) && defined('USE_UTF_STRINGS') && USE_UTF_STRINGS) {
            // If the lexer uses UtfString for processing then the position will
            // represent the position of the character and not the position of
            // the byte.
            $str = new UtfString($str);
        }

        // The reason for using the strlen is that the length
        // required is the length in bytes, not characters.
        //
        // Given the following string: `????+`, where `?` represents a
        // multi-byte character (lets assume that every `?` is a 2-byte
        // character) and `+` is a newline, the first value of `$i` is `0`
        // and the last one is `4` (because there are 5 characters). Bytes
        // `$str[0]` and `$str[1]` are the first character, `$str[2]` and
        // `$str[3]` are the second one and `$str[4]` is going to be the
        // first byte of the third character. The fourth and the last one
        // (which is actually a new line) aren't going to be processed at
        // all.
        $len = $str instanceof UtfString ?
            $str->length() : strlen($str);

        $lines = [0];
        for ($i = 0; $i < $len; ++$i) {
            if ($str[$i] !== "\n") {
                continue;
            }

            $lines[] = $i + 1;
        }

        return $lines;
    }

    /**
     * Computes the number of the line and column given an absolute position.
     *
     * @param array $lines The starting position of each line.
     * @param int   $pos   The absolute position
     * @psalm-param list<int> $lines
     *
     * @return array
     * @psalm-return array{int, int}
     */
    public static function findLineNumberAndColumn(array $lines, $pos)
    {
        $line = 0;
        foreach ($lines as $lineNo => $lineStart) {
            if ($lineStart > $pos) {
                break;
            }

            $line = $lineNo;
        }

        return [
            $line,
            $pos - $lines[$line],
        ];
    }

    /**
     * Runs the linting process.
     *
     * @param string $query The query to be checked.
     *
     * @return array
     * @psalm-return list<array{
     *   message: string,
     *   fromLine: int,
     *   fromColumn: int,
     *   toLine: int,
     *   toColumn: int,
     *   severity: string,
     * }>
     */
    public static function lint($query)
    {
        // Disabling lint for huge queries to save some resources.
        if (mb_strlen($query) > 10000) {
            return [
                [
                    'message' => __('Linting is disabled for this query because it exceeds the maximum length.'),
                    'fromLine' => 0,
                    'fromColumn' => 0,
                    'toLine' => 0,
                    'toColumn' => 0,
                    'severity' => 'warning',
                ],
            ];
        }

        /**
         * Lexer used for tokenizing the query.
         */
        $lexer = new Lexer($query);

        /**
         * Parsed used for analysing the query.
         */
        $parser = new Parser($lexer->list);

        /**
         * Array containing all errors.
         */
        $errors = ParserError::get([$lexer, $parser]);

        /**
         * The response containing of all errors.
         */
        $response = [];

        /**
         * The starting position for each line.
         *
         * CodeMirror requires relative position to line, but the parser stores
         * only the absolute position of the character in string.
         */
        $lines = static::getLines($query);

        // Building the response.
        foreach ($errors as $error) {
            // Starting position of the string that caused the error.
            [$fromLine, $fromColumn] = static::findLineNumberAndColumn($lines, $error[3]);

            // Ending position of the string that caused the error.
            [$toLine, $toColumn] = static::findLineNumberAndColumn(
                $lines,
                $error[3] + mb_strlen((string) $error[2])
            );

            // Building the response.
            $response[] = [
                'message' => sprintf(
                    __('%1$s (near <code>%2$s</code>)'),
                    htmlspecialchars((string) $error[0]),
                    htmlspecialchars((string) $error[2])
                ),
                'fromLine' => $fromLine,
                'fromColumn' => $fromColumn,
                'toLine' => $toLine,
                'toColumn' => $toColumn,
                'severity' => 'error',
            ];
        }

        // Sending back the answer.
        return $response;
    }
}
