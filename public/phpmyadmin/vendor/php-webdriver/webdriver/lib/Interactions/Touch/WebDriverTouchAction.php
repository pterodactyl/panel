<?php

namespace Facebook\WebDriver\Interactions\Touch;

use Facebook\WebDriver\Interactions\Internal\WebDriverCoordinates;
use Facebook\WebDriver\Internal\WebDriverLocatable;

/**
 * Base class for all touch-related actions.
 */
abstract class WebDriverTouchAction
{
    /**
     * @var WebDriverTouchScreen
     */
    protected $touchScreen;
    /**
     * @var WebDriverLocatable
     */
    protected $locationProvider;

    /**
     * @param WebDriverTouchScreen $touch_screen
     * @param WebDriverLocatable $location_provider
     */
    public function __construct(
        WebDriverTouchScreen $touch_screen,
        WebDriverLocatable $location_provider = null
    ) {
        $this->touchScreen = $touch_screen;
        $this->locationProvider = $location_provider;
    }

    /**
     * @return null|WebDriverCoordinates
     */
    protected function getActionLocation()
    {
        return $this->locationProvider !== null
            ? $this->locationProvider->getCoordinates() : null;
    }
}
