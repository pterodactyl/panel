<?php

namespace Facebook\WebDriver;

/**
 * Interface implemented by each driver that allows access to the input devices.
 */
interface WebDriverHasInputDevices
{
    /**
     * @return WebDriverKeyboard
     */
    public function getKeyboard();

    /**
     * @return WebDriverMouse
     */
    public function getMouse();
}
