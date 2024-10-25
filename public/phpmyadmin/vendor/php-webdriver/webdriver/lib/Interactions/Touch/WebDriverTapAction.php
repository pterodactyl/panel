<?php

namespace Facebook\WebDriver\Interactions\Touch;

use Facebook\WebDriver\WebDriverAction;

class WebDriverTapAction extends WebDriverTouchAction implements WebDriverAction
{
    public function perform()
    {
        $this->touchScreen->tap($this->locationProvider);
    }
}
