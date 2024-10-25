<?php

namespace Facebook\WebDriver\Interactions\Internal;

use Facebook\WebDriver\Internal\WebDriverLocatable;
use Facebook\WebDriver\WebDriverMouse;

/**
 * Base class for all mouse-related actions.
 */
class WebDriverMouseAction
{
    /**
     * @var WebDriverMouse
     */
    protected $mouse;
    /**
     * @var WebDriverLocatable
     */
    protected $locationProvider;

    /**
     * @param WebDriverMouse $mouse
     * @param WebDriverLocatable|null $location_provider
     */
    public function __construct(WebDriverMouse $mouse, WebDriverLocatable $location_provider = null)
    {
        $this->mouse = $mouse;
        $this->locationProvider = $location_provider;
    }

    /**
     * @return null|WebDriverCoordinates
     */
    protected function getActionLocation()
    {
        if ($this->locationProvider !== null) {
            return $this->locationProvider->getCoordinates();
        }

        return null;
    }

    protected function moveToLocation()
    {
        $this->mouse->mouseMove($this->locationProvider);
    }
}
