<?php

namespace Facebook\WebDriver\Interactions\Internal;

use Facebook\WebDriver\WebDriverAction;

class WebDriverClickAction extends WebDriverMouseAction implements WebDriverAction
{
    public function perform()
    {
        $this->mouse->click($this->getActionLocation());
    }
}
