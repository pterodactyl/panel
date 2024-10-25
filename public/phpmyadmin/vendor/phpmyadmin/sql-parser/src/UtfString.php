<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use ArrayAccess;
use Exception;
use Stringable;

use function mb_check_encoding;
use function mb_strlen;
use function ord;

/**
 * Implementation for UTF-8 strings.
 *
 * The subscript operator in PHP, when used with string will return a byte and not a character. Because in UTF-8
 * strings a character may occupy more than one byte, the subscript operator may return an invalid character.
 *
 * Because the lexer relies on the subscript operator this class had to be implemented.
 *
 * Implements array-like access for UTF-8 strings.
 *
 * In this library, this class should be used to parse UTF-8 queries.
 *
 * @implements ArrayAccess<int, string>
 */
class UtfString implements ArrayAccess, Stringable
{
    /**
     * The raw, multi-byte string.
     *
     * @var string
     */
    public $str = '';

    /**
     * The index of current byte.
     *
     * For ASCII strings, the byte index is equal to the character index.
     *
     * @var int
     */
    public $byteIdx = 0;

    /**
     * The index of current character.
     *
     * For non-ASCII strings, some characters occupy more than one byte and
     * the character index will have a lower value than the byte index.
     *
     * @var int
     */
    public $charIdx = 0;

    /**
     * The length of the string (in bytes).
     *
     * @var int
     */
    public $byteLen = 0;

    /**
     * The length of the string (in characters).
     *
     * @var int
     */
    public $charLen = 0;

    /**
     * A map of ASCII binary values to their ASCII code
     * This is to improve performance and avoid calling ord($byte)
     *
     * Source: https://www.freecodecamp.org/news/ascii-table-hex-to-ascii-value-character-code-chart-2/
     *
     * @var array<int|string,int>
     */
    protected static $asciiMap = [
        "\0" => 0, // (00000000) NUL Null
        "\t" => 9, // (00001001) HT Horizontal Tab
        "\n" => 10, // (00001010) LF Newline / Line Feed
        "\v" => 11, // (00001011) VT Vertical Tab
        "\f" => 12, // (00001100) FF Form Feed
        "\r" => 13, // (00001101) CR Carriage Return
        ' ' => 32, // (00100000) SP Space
        '!' => 33, // (00100001) ! Exclamation mark
        '"' => 34, // (00100010) " Double quote
        '#' => 35, // (00100011) # Number
        '$' => 36, // (00100100) $ Dollar
        '%' => 37, // (00100101) % Percent
        '&' => 38, // (00100110) & Ampersand
        '\'' => 39, // (00100111) ' Single quote
        '(' => 40, // (00101000) ( Left parenthesis
        ')' => 41, // (00101001) ) Right parenthesis
        '*' => 42, // (00101010) * Asterisk
        '+' => 43, // (00101011) + Plus
        ',' => 44, // (00101100) , Comma
        '-' => 45, // (00101101) - Minus
        '.' => 46, // (00101110) . Period
        '/' => 47, // (00101111) / Slash
        '0' => 48, // (00110000) 0 Zero
        '1' => 49, // (00110001) 1 One
        '2' => 50, // (00110010) 2 Two
        '3' => 51, // (00110011) 3 Three
        '4' => 52, // (00110100) 4 Four
        '5' => 53, // (00110101) 5 Five
        '6' => 54, // (00110110) 6 Six
        '7' => 55, // (00110111) 7 Seven
        '8' => 56, // (00111000) 8 Eight
        '9' => 57, // (00111001) 9 Nine
        ':' => 58, // (00111010) : Colon
        ';' => 59, // (00111011) ; Semicolon
        '<' => 60, // (00111100) < Less than
        '=' => 61, // (00111101) = Equal sign
        '>' => 62, // (00111110) > Greater than
        '?' => 63, // (00111111) ? Question mark
        '@' => 64, // (01000000) @ At sign
        'A' => 65, // (01000001) A Uppercase A
        'B' => 66, // (01000010) B Uppercase B
        'C' => 67, // (01000011) C Uppercase C
        'D' => 68, // (01000100) D Uppercase D
        'E' => 69, // (01000101) E Uppercase E
        'F' => 70, // (01000110) F Uppercase F
        'G' => 71, // (01000111) G Uppercase G
        'H' => 72, // (01001000) H Uppercase H
        'I' => 73, // (01001001) I Uppercase I
        'J' => 74, // (01001010) J Uppercase J
        'K' => 75, // (01001011) K Uppercase K
        'L' => 76, // (01001100) L Uppercase L
        'M' => 77, // (01001101) M Uppercase M
        'N' => 78, // (01001110) N Uppercase N
        'O' => 79, // (01001111) O Uppercase O
        'P' => 80, // (01010000) P Uppercase P
        'Q' => 81, // (01010001) Q Uppercase Q
        'R' => 82, // (01010010) R Uppercase R
        'S' => 83, // (01010011) S Uppercase S
        'T' => 84, // (01010100) T Uppercase T
        'U' => 85, // (01010101) U Uppercase U
        'V' => 86, // (01010110) V Uppercase V
        'W' => 87, // (01010111) W Uppercase W
        'X' => 88, // (01011000) X Uppercase X
        'Y' => 89, // (01011001) Y Uppercase Y
        'Z' => 90, // (01011010) Z Uppercase Z
        '[' => 91, // (01011011) [ Left square bracket
        '\\' => 92, // (01011100) \ backslash
        ']' => 93, // (01011101) ] Right square bracket
        '^' => 94, // (01011110) ^ Caret / circumflex
        '_' => 95, // (01011111) _ Underscore
        '`' => 96, // (01100000) ` Grave / accent
        'a' => 97, // (01100001) a Lowercase a
        'b' => 98, // (01100010) b Lowercase b
        'c' => 99, // (01100011) c Lowercase c
        'd' => 100, // (01100100) d Lowercase d
        'e' => 101, // (01100101) e Lowercase e
        'f' => 102, // (01100110) f Lowercase
        'g' => 103, // (01100111) g Lowercase g
        'h' => 104, // (01101000) h Lowercase h
        'i' => 105, // (01101001) i Lowercase i
        'j' => 106, // (01101010) j Lowercase j
        'k' => 107, // (01101011) k Lowercase k
        'l' => 108, // (01101100) l Lowercase l
        'm' => 109, // (01101101) m Lowercase m
        'n' => 110, // (01101110) n Lowercase n
        'o' => 111, // (01101111) o Lowercase o
        'p' => 112, // (01110000) p Lowercase p
        'q' => 113, // (01110001) q Lowercase q
        'r' => 114, // (01110010) r Lowercase r
        's' => 115, // (01110011) s Lowercase s
        't' => 116, // (01110100) t Lowercase t
        'u' => 117, // (01110101) u Lowercase u
        'v' => 118, // (01110110) v Lowercase v
        'w' => 119, // (01110111) w Lowercase w
        'x' => 120, // (01111000) x Lowercase x
        'y' => 121, // (01111001) y Lowercase y
        'z' => 122, // (01111010) z Lowercase z
        '{' => 123, // (01111011) { Left curly bracket
        '|' => 124, // (01111100) | Vertical bar
        '}' => 125, // (01111101) } Right curly bracket
        '~' => 126, // (01111110) ~ Tilde
        "\x7f" => 127, // (01111111) DEL Delete
    ];

    /**
     * @param string $str the string
     */
    public function __construct($str)
    {
        $this->str = $str;
        $this->byteIdx = 0;
        $this->charIdx = 0;
        $this->byteLen = mb_strlen($str, '8bit');
        if (! mb_check_encoding($str, 'UTF-8')) {
            $this->charLen = 0;
        } else {
            $this->charLen = mb_strlen($str, 'UTF-8');
        }
    }

    /**
     * Checks if the given offset exists.
     *
     * @param int $offset the offset to be checked
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return ($offset >= 0) && ($offset < $this->charLen);
    }

    /**
     * Gets the character at given offset.
     *
     * @param int $offset the offset to be returned
     *
     * @return string|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (($offset < 0) || ($offset >= $this->charLen)) {
            return null;
        }

        $delta = $offset - $this->charIdx;

        if ($delta > 0) {
            // Fast forwarding.
            while ($delta-- > 0) {
                $this->byteIdx += static::getCharLength($this->str[$this->byteIdx]);
                ++$this->charIdx;
            }
        } elseif ($delta < 0) {
            // Rewinding.
            while ($delta++ < 0) {
                do {
                    $byte = ord($this->str[--$this->byteIdx]);
                } while (($byte >= 128) && ($byte < 192));

                --$this->charIdx;
            }
        }

        $bytesCount = static::getCharLength($this->str[$this->byteIdx]);

        $ret = '';
        for ($i = 0; $bytesCount-- > 0; ++$i) {
            $ret .= $this->str[$this->byteIdx + $i];
        }

        return $ret;
    }

    /**
     * Sets the value of a character.
     *
     * @param int    $offset the offset to be set
     * @param string $value  the value to be set
     *
     * @return void
     *
     * @throws Exception not implemented.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new Exception('Not implemented.');
    }

    /**
     * Unsets an index.
     *
     * @param int $offset the value to be unset
     *
     * @return void
     *
     * @throws Exception not implemented.
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new Exception('Not implemented.');
    }

    /**
     * Gets the length of an UTF-8 character.
     *
     * According to RFC 3629, a UTF-8 character can have at most 4 bytes.
     * However, this implementation supports UTF-8 characters containing up to 6
     * bytes.
     *
     * @see https://tools.ietf.org/html/rfc3629
     *
     * @param string $byte the byte to be analyzed
     *
     * @return int
     */
    public static function getCharLength($byte)
    {
        // Use the default ASCII map as queries are mostly ASCII chars
        // ord($byte) has a performance cost

        if (! isset(static::$asciiMap[$byte])) {
            // Complete the cache with missing items
            static::$asciiMap[$byte] = ord($byte);
        }

        $byte = static::$asciiMap[$byte];

        if ($byte < 128) {
            return 1;
        }

        if ($byte < 224) {
            return 2;
        }

        if ($byte < 240) {
            return 3;
        }

        if ($byte < 248) {
            return 4;
        }

        if ($byte < 252) {
            return 5; // unofficial
        }

        return 6; // unofficial
    }

    /**
     * Returns the length in characters of the string.
     *
     * @return int
     */
    public function length()
    {
        return $this->charLen;
    }

    /**
     * Returns the contained string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->str;
    }
}
