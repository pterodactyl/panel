<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Exceptions;

use Log;
use Throwable;

class DisplayException extends PterodactylException
{
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    /**
     * @var string
     */
    protected $level;

    /**
     * Exception constructor.
     *
     * @param string         $message
     * @param Throwable|null $previous
     * @param string         $level
     * @param int            $code
     */
    public function __construct($message, Throwable $previous = null, $level = self::LEVEL_ERROR, $code = 0)
    {
        $this->level = $level;

        if (! is_null($previous)) {
            Log::{$level}($previous);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getErrorLevel()
    {
        return $this->level;
    }
}
