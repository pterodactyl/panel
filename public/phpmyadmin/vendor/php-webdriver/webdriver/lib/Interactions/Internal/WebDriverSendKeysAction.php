<?php

namespace Facebook\WebDriver\Interactions\Internal;

use Facebook\WebDriver\Internal\WebDriverLocatable;
use Facebook\WebDriver\WebDriverAction;
use Facebook\WebDriver\WebDriverKeyboard;
use Facebook\WebDriver\WebDriverMouse;

class WebDriverSendKeysAction extends WebDriverKeysRelatedAction implements WebDriverAction
{
    /**
     * @var string
     */
    private $keys = '';

    /**
     * @param WebDriverKeyboard $keyboard
     * @param WebDriverMouse $mouse
     * @param WebDriverLocatable $location_provider
     * @param string $keys
     */
    public function __construct(
        WebDriverKeyboard $keyboard,
        WebDriverMouse $mouse,
        WebDriverLocatable $location_provider = null,
        $keys = ''
    ) {
        parent::__construct($keyboard, $mouse, $location_provider);
        $this->keys = $keys;
    }

    public function perform()
    {
        $this->focusOnElement();
        $this->keyboard->sendKeys($this->keys);
    }
}
