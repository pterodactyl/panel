<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Exceptions;

use Exception;
use PhpMyAdmin\SqlParser\Token;

/**
 * Exception thrown by the parser.
 */
class ParserException extends Exception
{
    /**
     * The token that produced this error.
     *
     * @var Token|null
     */
    public $token;

    /**
     * @param string     $msg   the message of this exception
     * @param Token|null $token the token that produced this exception
     * @param int        $code  the code of this error
     */
    public function __construct($msg = '', ?Token $token = null, $code = 0)
    {
        parent::__construct($msg, $code);
        $this->token = $token;
    }
}
