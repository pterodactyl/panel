<?php

namespace Facebook\WebDriver\Interactions\Internal;

use Facebook\WebDriver\Internal\WebDriverLocatable;
use Facebook\WebDriver\WebDriverKeyboard;
use Facebook\WebDriver\WebDriverMouse;

/**
 * Base class for all keyboard-related actions.
 */
abstract class WebDriverKeysRelatedAction
{
    /**
     * @var WebDriverKeyboard
     */
    protected $keyboard;
    /**
     * @var WebDriverMouse
     */
    protected $mouse;
    /**
     * @var WebDriverLocatable|null
     */
    protected $locationProvider;

    /**
     * @param WebDriverKeyboard $keyboard
     * @param WebDriverMouse $mouse
     * @param WebDriverLocatable $location_provider
     */
    public function __construct(
        WebDriverKeyboard $keyboard,
        WebDriverMouse $mouse,
        WebDriverLocatable $location_provider = null
    ) {
        $this->keyboard = $keyboard;
        $this->mouse = $mouse;
        $this->locationProvider = $location_provider;
    }

    protected function focusOnElement()
    {
        if ($this->locationProvider) {
            $this->mouse->click($this->locationProvider->getCoordinates());
        }
    }
}
