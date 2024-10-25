<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Interactions\Touch\WebDriverTouchAction;
use Facebook\WebDriver\Interactions\Touch\WebDriverTouchScreen;

class WebDriverUpAction extends WebDriverTouchAction implements WebDriverAction
{
    private $x;
    private $y;

    /**
     * @param WebDriverTouchScreen $touch_screen
     * @param int $x
     * @param int $y
     */
    public function __construct(WebDriverTouchScreen $touch_screen, $x, $y)
    {
        $this->x = $x;
        $this->y = $y;
        parent::__construct($touch_screen);
    }

    public function perform()
    {
        $this->touchScreen->up($this->x, $this->y);
    }
}
