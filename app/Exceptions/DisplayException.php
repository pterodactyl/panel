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

class DisplayException extends PterodactylException
{
    /**
     * Exception constructor.
     *
     * @param string $message
     * @param mixed  $log
     */
    public function __construct($message, $log = null)
    {
        if (! is_null($log)) {
            Log::error($log);
        }

        parent::__construct($message);
    }
}
