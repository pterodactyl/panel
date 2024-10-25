<?php

namespace Facebook\WebDriver\Interactions\Touch;

use Facebook\WebDriver\WebDriverAction;

class WebDriverDownAction extends WebDriverTouchAction implements WebDriverAction
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
        $this->touchScreen->down($this->x, $this->y);
    }
}
