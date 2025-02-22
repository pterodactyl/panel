<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use ArrayAccess;

use function array_splice;
use function count;
use function in_array;
use function is_array;
use function is_string;

/**
 * Defines an array of tokens and utility functions to iterate through it.
 *
 * A structure representing a list of tokens.
 *
 * @implements ArrayAccess<int, Token>
 */
class TokensList implements ArrayAccess
{
    /**
     * The array of tokens.
     *
     * @var Token[]
     */
    public $tokens = [];

    /**
     * The count of tokens.
     *
     * @var int
     */
    public $count = 0;

    /**
     * The index of the next token to be returned.
     *
     * @var int
     */
    public $idx = 0;

    /**
     * @param Token[] $tokens the initial array of tokens
     * @param int     $count  the count of tokens in the initial array
     */
    public function __construct(array $tokens = [], $count = -1)
    {
        if (empty($tokens)) {
            return;
        }

        $this->tokens = $tokens;
        $this->count = $count === -1 ? count($tokens) : $count;
    }

    /**
     * Builds an array of tokens by merging their raw value.
     *
     * @param string|Token[]|TokensList $list the tokens to be built
     *
     * @return string
     */
    public static function build($list)
    {
        if (is_string($list)) {
            return $list;
        }

        if ($list instanceof self) {
            $list = $list->tokens;
        }

        $ret = '';
        if (is_array($list)) {
            foreach ($list as $tok) {
                $ret .= $tok->token;
            }
        }

        return $ret;
    }

    /**
     * Adds a new token.
     *
     * @param Token $token token to be added in list
     *
     * @return void
     */
    public function add(Token $token)
    {
        $this->tokens[$this->count++] = $token;
    }

    /**
     * Gets the next token. Skips any irrelevant token (whitespaces and
     * comments).
     *
     * @return Token|null
     */
    public function getNext()
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if (
                ($this->tokens[$this->idx]->type !== Token::TYPE_WHITESPACE)
                && ($this->tokens[$this->idx]->type !== Token::TYPE_COMMENT)
            ) {
                return $this->tokens[$this->idx++];
            }
        }

        return null;
    }

    /**
     * Gets the previous token. Skips any irrelevant token (whitespaces and
     * comments).
     */
    public function getPrevious(): ?Token
    {
        for (; $this->idx >= 0; --$this->idx) {
            if (
                ($this->tokens[$this->idx]->type !== Token::TYPE_WHITESPACE)
                && ($this->tokens[$this->idx]->type !== Token::TYPE_COMMENT)
            ) {
                return $this->tokens[$this->idx--];
            }
        }

        return null;
    }

    /**
     * Gets the previous token.
     *
     * @param int|int[] $type the type
     *
     * @return Token|null
     */
    public function getPreviousOfType($type)
    {
        if (! is_array($type)) {
            $type = [$type];
        }

        for (; $this->idx >= 0; --$this->idx) {
            if (in_array($this->tokens[$this->idx]->type, $type, true)) {
                return $this->tokens[$this->idx--];
            }
        }

        return null;
    }

    /**
     * Gets the next token.
     *
     * @param int|int[] $type the type
     *
     * @return Token|null
     */
    public function getNextOfType($type)
    {
        if (! is_array($type)) {
            $type = [$type];
        }

        for (; $this->idx < $this->count; ++$this->idx) {
            if (in_array($this->tokens[$this->idx]->type, $type, true)) {
                return $this->tokens[$this->idx++];
            }
        }

        return null;
    }

    /**
     * Gets the next token.
     *
     * @param int    $type  the type of the token
     * @param string $value the value of the token
     *
     * @return Token|null
     */
    public function getNextOfTypeAndValue($type, $value)
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if (($this->tokens[$this->idx]->type === $type) && ($this->tokens[$this->idx]->value === $value)) {
                return $this->tokens[$this->idx++];
            }
        }

        return null;
    }

    /**
     * Gets the next token.
     *
     * @param int $type the type of the token
     * @param int $flag the flag of the token
     */
    public function getNextOfTypeAndFlag(int $type, int $flag): ?Token
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if (($this->tokens[$this->idx]->type === $type) && ($this->tokens[$this->idx]->flags === $flag)) {
                return $this->tokens[$this->idx++];
            }
        }

        return null;
    }

    /**
     * Sets a Token inside the list of tokens.
     * When defined, offset must be positive otherwise the offset is ignored.
     * If the offset is not defined (like in array_push) or if it is greater than the number of Tokens already stored,
     * the Token is appended to the list of tokens.
     *
     * @param int|null $offset the offset to be set. Must be positive otherwise, nothing will be stored.
     * @param Token    $value  the token to be saved
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if ($offset === null || $offset >= $this->count) {
            $this->tokens[$this->count++] = $value;
        } elseif ($offset >= 0) {
            $this->tokens[$offset] = $value;
        }
    }

    /**
     * Gets a Token from the list of tokens.
     * If the offset is negative or above the number of tokens set in the list, will return null.
     *
     * @param int $offset the offset to be returned
     *
     * @return Token|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->tokens[$offset] : null;
    }

    /**
     * Checks if an offset was previously set.
     * If the offset is negative or above the number of tokens set in the list, will return false.
     *
     * @param int $offset the offset to be checked
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $offset >= 0 && $offset < $this->count;
    }

    /**
     * Unsets the value of an offset, if the offset exists.
     *
     * @param int $offset the offset to be unset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        if (! $this->offsetExists($offset)) {
            return;
        }

        array_splice($this->tokens, $offset, 1);
        --$this->count;
    }
}
