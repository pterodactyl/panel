<?php

namespace Facebook\WebDriver\Remote;

use Facebook\WebDriver\Interactions\Touch\WebDriverTouchScreen;
use Facebook\WebDriver\WebDriverElement;

/**
 * Execute touch commands for RemoteWebDriver.
 */
class RemoteTouchScreen implements WebDriverTouchScreen
{
    /**
     * @var RemoteExecuteMethod
     */
    private $executor;

    /**
     * @param RemoteExecuteMethod $executor
     */
    public function __construct(RemoteExecuteMethod $executor)
    {
        $this->executor = $executor;
    }

    /**
     * @param WebDriverElement $element
     *
     * @return RemoteTouchScreen The instance.
     */
    public function tap(WebDriverElement $element)
    {
        $this->executor->execute(
            DriverCommand::TOUCH_SINGLE_TAP,
            ['element' => $element->getID()]
        );

        return $this;
    }

    /**
     * @param WebDriverElement $element
     *
     * @return RemoteTouchScreen The instance.
     */
    public function doubleTap(WebDriverElement $element)
    {
        $this->executor->execute(
            DriverCommand::TOUCH_DOUBLE_TAP,
            ['element' => $element->getID()]
        );

        return $this;
    }

    /**
     * @param int $x
     * @param int $y
     *
     * @return RemoteTouchScreen The instance.
     */
    public function down($x, $y)
    {
        $this->executor->execute(DriverCommand::TOUCH_DOWN, [
            'x' => $x,
            'y' => $y,
        ]);

        return $this;
    }

    /**
     * @param int $xspeed
     * @param int $yspeed
     *
     * @return RemoteTouchScreen The instance.
     */
    public function flick($xspeed, $yspeed)
    {
        $this->executor->execute(DriverCommand::TOUCH_FLICK, [
            'xspeed' => $xspeed,
            'yspeed' => $yspeed,
        ]);

        return $this;
    }

    /**
     * @param WebDriverElement $element
     * @param int $xoffset
     * @param int $yoffset
     * @param int $speed
     *
     * @return RemoteTouchScreen The instance.
     */
    public function flickFromElement(WebDriverElement $element, $xoffset, $yoffset, $speed)
    {
        $this->executor->execute(DriverCommand::TOUCH_FLICK, [
            'xoffset' => $xoffset,
            'yoffset' => $yoffset,
            'element' => $element->getID(),
            'speed' => $speed,
        ]);

        return $this;
    }

    /**
     * @param WebDriverElement $element
     *
     * @return RemoteTouchScreen The instance.
     */
    public function longPress(WebDriverElement $element)
    {
        $this->executor->execute(
            DriverCommand::TOUCH_LONG_PRESS,
            ['element' => $element->getID()]
        );

        return $this;
    }

    /**
     * @param int $x
     * @param int $y
     *
     * @return RemoteTouchScreen The instance.
     */
    public function move($x, $y)
    {
        $this->executor->execute(DriverCommand::TOUCH_MOVE, [
            'x' => $x,
            'y' => $y,
        ]);

        return $this;
    }

    /**
     * @param int $xoffset
     * @param int $yoffset
     *
     * @return RemoteTouchScreen The instance.
     */
    public function scroll($xoffset, $yoffset)
    {
        $this->executor->execute(DriverCommand::TOUCH_SCROLL, [
            'xoffset' => $xoffset,
            'yoffset' => $yoffset,
        ]);

        return $this;
    }

    /**
     * @param WebDriverElement $element
     * @param int $xoffset
     * @param int $yoffset
     *
     * @return RemoteTouchScreen The instance.
     */
    public function scrollFromElement(WebDriverElement $element, $xoffset, $yoffset)
    {
        $this->executor->execute(DriverCommand::TOUCH_SCROLL, [
            'element' => $element->getID(),
            'xoffset' => $xoffset,
            'yoffset' => $yoffset,
        ]);

        return $this;
    }

    /**
     * @param int $x
     * @param int $y
     *
     * @return RemoteTouchScreen The instance.
     */
    public function up($x, $y)
    {
        $this->executor->execute(DriverCommand::TOUCH_UP, [
            'x' => $x,
            'y' => $y,
        ]);

        return $this;
    }
}
