<?php

namespace Facebook\WebDriver\Interactions\Touch;

use Facebook\WebDriver\WebDriverAction;

class WebDriverLongPressAction extends WebDriverTouchAction implements WebDriverAction
{
    public function perform()
    {
        $this->touchScreen->longPress($this->locationProvider);
    }
}
