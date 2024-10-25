<?php

namespace Facebook\WebDriver\Interactions\Internal;

class WebDriverKeyUpAction extends WebDriverSingleKeyAction
{
    public function perform()
    {
        $this->focusOnElement();
        $this->keyboard->releaseKey($this->key);
    }
}
