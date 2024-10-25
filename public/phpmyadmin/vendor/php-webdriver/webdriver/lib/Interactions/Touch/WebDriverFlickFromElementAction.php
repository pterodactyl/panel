<?php

namespace Facebook\WebDriver\Interactions\Touch;

use Facebook\WebDriver\WebDriverAction;
use Facebook\WebDriver\WebDriverElement;

class WebDriverFlickFromElementAction extends WebDriverTouchAction implements WebDriverAction
{
    /**
     * @var int
     */
    private $x;
    /**
     * @var int
     */
    private $y;
    /**
     * @var int
     */
    private $speed;

    /**
     * @param WebDriverTouchScreen $touch_screen
     * @param WebDriverElement $element
     * @param int $x
     * @param int $y
     * @param int $speed
     */
    public function __construct(
        WebDriverTouchScreen $touch_screen,
        WebDriverElement $element,
        $x,
        $y,
        $speed
    ) {
        $this->x = $x;
        $this->y = $y;
        $this->speed = $speed;
        parent::__construct($touch_screen, $element);
    }

    public function perform()
    {
        $this->touchScreen->flickFromElement(
            $this->locationProvider,
            $this->x,
            $this->y,
            $this->speed
        );
    }
}
