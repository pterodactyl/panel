<?php

namespace Facebook\WebDriver\Interactions\Internal;

use Facebook\WebDriver\WebDriverAction;

/**
 * Move to the location and then release the mouse key.
 */
class WebDriverButtonReleaseAction extends WebDriverMouseAction implements WebDriverAction
{
    public function perform()
    {
        $this->mouse->mouseUp($this->getActionLocation());
    }
}
