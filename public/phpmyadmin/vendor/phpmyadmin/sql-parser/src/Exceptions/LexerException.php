<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Exceptions;

use Exception;

/**
 * Exception thrown by the lexer.
 */
class LexerException extends Exception
{
    /**
     * The character that produced this error.
     *
     * @var string
     */
    public $ch;

    /**
     * The index of the character that produced this error.
     *
     * @var int
     */
    public $pos;

    /**
     * @param string $msg  the message of this exception
     * @param string $ch   the character that produced this exception
     * @param int    $pos  the position of the character
     * @param int    $code the code of this error
     */
    public function __construct($msg = '', $ch = '', $pos = 0, $code = 0)
    {
        parent::__construct($msg, $code);
        $this->ch = $ch;
        $this->pos = $pos;
    }
}
