<?php

namespace Facebook\WebDriver\Interactions\Internal;

use Facebook\WebDriver\WebDriverAction;

/**
 * You can call it 'Right Click' if you like.
 */
class WebDriverContextClickAction extends WebDriverMouseAction implements WebDriverAction
{
    public function perform()
    {
        $this->mouse->contextClick($this->getActionLocation());
    }
}
