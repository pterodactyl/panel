<?php

namespace Facebook\WebDriver\Interactions\Touch;

use Facebook\WebDriver\WebDriverElement;

/**
 * Interface representing touch screen operations.
 */
interface WebDriverTouchScreen
{
    /**
     * Single tap on the touch enabled device.
     *
     * @param WebDriverElement $element
     * @return $this
     */
    public function tap(WebDriverElement $element);

    /**
     * Double tap on the touch screen using finger motion events.
     *
     * @param WebDriverElement $element
     * @return $this
     */
    public function doubleTap(WebDriverElement $element);

    /**
     * Finger down on the screen.
     *
     * @param int $x
     * @param int $y
     * @return $this
     */
    public function down($x, $y);

    /**
     * Flick on the touch screen using finger motion events. Use this flick
     * command if you don't care where the flick starts on the screen.
     *
     * @param int $xspeed
     * @param int $yspeed
     * @return $this
     */
    public function flick($xspeed, $yspeed);

    /**
     * Flick on the touch screen using finger motion events.
     * This flickcommand starts at a particular screen location.
     *
     * @param WebDriverElement $element
     * @param int $xoffset
     * @param int $yoffset
     * @param int $speed
     * @return $this
     */
    public function flickFromElement(
        WebDriverElement $element,
        $xoffset,
        $yoffset,
        $speed
    );

    /**
     * Long press on the touch screen using finger motion events.
     *
     * @param WebDriverElement $element
     * @return $this
     */
    public function longPress(WebDriverElement $element);

    /**
     * Finger move on the screen.
     *
     * @param int $x
     * @param int $y
     * @return $this
     */
    public function move($x, $y);

    /**
     * Scroll on the touch screen using finger based motion events. Use this
     * command if you don't care where the scroll starts on the screen.
     *
     * @param int $xoffset
     * @param int $yoffset
     * @return $this
     */
    public function scroll($xoffset, $yoffset);

    /**
     * Scroll on the touch screen using finger based motion events. Use this
     * command to start scrolling at a particular screen location.
     *
     * @param WebDriverElement $element
     * @param int $xoffset
     * @param int $yoffset
     * @return $this
     */
    public function scrollFromElement(
        WebDriverElement $element,
        $xoffset,
        $yoffset
    );

    /**
     * Finger up on the screen.
     *
     * @param int $x
     * @param int $y
     * @return $this
     */
    public function up($x, $y);
}
