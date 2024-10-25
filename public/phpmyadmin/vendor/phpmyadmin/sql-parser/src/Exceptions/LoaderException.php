<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Exceptions;

use Exception;

/**
 * Exception thrown by the lexer.
 */
class LoaderException extends Exception
{
    /**
     * The failed load name.
     *
     * @var string
     */
    public $name;

    /**
     * @param string $msg  the message of this exception
     * @param string $name the character that produced this exception
     * @param int    $code the code of this error
     */
    public function __construct($msg = '', $name = '', $code = 0)
    {
        parent::__construct($msg, $code);
        $this->name = $name;
    }
}
