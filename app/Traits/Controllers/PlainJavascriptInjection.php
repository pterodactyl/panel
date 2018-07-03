<?php
/**
 * Created by PhpStorm.
 * User: Stan
 * Date: 26-5-2018
 * Time: 20:56.
 */

namespace Pterodactyl\Traits\Controllers;

use JavaScript;

trait PlainJavascriptInjection
{
    /**
     * Injects statistics into javascript.
     */
    public function injectJavascript($data)
    {
        Javascript::put($data);
    }
}
