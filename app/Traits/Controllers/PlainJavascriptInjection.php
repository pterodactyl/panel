<?php

namespace Pterodactyl\Traits\Controllers;

use JavaScript;

trait PlainJavascriptInjection
{
    /**
     * Injects statistics into javascript.
     */
    public function injectJavascript($data)
    {
        \JavaScript::put($data);
    }
}
