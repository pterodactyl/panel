<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use Exception;

/**
 * Defines the core helper infrastructure of the library.
 */
class Core
{
    /**
     * Whether errors should throw exceptions or just be stored.
     *
     * @see static::$errors
     *
     * @var bool
     */
    public $strict = false;

    /**
     * List of errors that occurred during lexing.
     *
     * Usually, the lexing does not stop once an error occurred because that
     * error might be false positive or a partial result (even a bad one)
     * might be needed.
     *
     * @see Core::error()
     *
     * @var Exception[]
     */
    public $errors = [];

    /**
     * Creates a new error log.
     *
     * @param Exception $error the error exception
     *
     * @return void
     *
     * @throws Exception throws the exception, if strict mode is enabled.
     */
    public function error($error)
    {
        if ($this->strict) {
            throw $error;
        }

        $this->errors[] = $error;
    }
}
