<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use ArrayAccess;

use function count;
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
        for (; $this->idx > 0; --$this->idx) {
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
     * Gets the next token.
     *
     * @param int $type the type
     *
     * @return Token|null
     */
    public function getNextOfType($type)
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if ($this->tokens[$this->idx]->type === $type) {
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
     * Sets an value inside the container.
     *
     * @param int|null $offset the offset to be set
     * @param Token    $value  the token to be saved
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->tokens[$this->count++] = $value;
        } else {
            $this->tokens[$offset] = $value;
        }
    }

    /**
     * Gets a value from the container.
     *
     * @param int $offset the offset to be returned
     *
     * @return Token|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $offset < $this->count ? $this->tokens[$offset] : null;
    }

    /**
     * Checks if an offset was previously set.
     *
     * @param int $offset the offset to be checked
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $offset < $this->count;
    }

    /**
     * Unsets the value of an offset.
     *
     * @param int $offset the offset to be unset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->tokens[$offset]);
        --$this->count;
        for ($i = $offset; $i < $this->count; ++$i) {
            $this->tokens[$i] = $this->tokens[$i + 1];
        }

        unset($this->tokens[$this->count]);
    }
}
