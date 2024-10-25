<?php

namespace Facebook\WebDriver\Interactions\Internal;

use Facebook\WebDriver\WebDriverAction;

class WebDriverMouseMoveAction extends WebDriverMouseAction implements WebDriverAction
{
    public function perform()
    {
        $this->mouse->mouseMove($this->getActionLocation());
    }
}
