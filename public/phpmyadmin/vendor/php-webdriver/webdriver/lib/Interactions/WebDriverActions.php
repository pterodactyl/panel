<?php

namespace Facebook\WebDriver\Interactions;

use Facebook\WebDriver\Interactions\Internal\WebDriverButtonReleaseAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverClickAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverClickAndHoldAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverContextClickAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverDoubleClickAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverKeyDownAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverKeyUpAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverMouseMoveAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverMoveToOffsetAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverSendKeysAction;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverHasInputDevices;

/**
 * WebDriver action builder. It implements the builder pattern.
 */
class WebDriverActions
{
    protected $driver;
    protected $keyboard;
    protected $mouse;
    protected $action;

    /**
     * @param WebDriverHasInputDevices $driver
     */
    public function __construct(WebDriverHasInputDevices $driver)
    {
        $this->driver = $driver;
        $this->keyboard = $driver->getKeyboard();
        $this->mouse = $driver->getMouse();
        $this->action = new WebDriverCompositeAction();
    }

    /**
     * A convenience method for performing the actions without calling build().
     */
    public function perform()
    {
        $this->action->perform();
    }

    /**
     * Mouse click.
     * If $element is provided, move to the middle of the element first.
     *
     * @param WebDriverElement $element
     * @return WebDriverActions
     */
    public function click(WebDriverElement $element = null)
    {
        $this->action->addAction(
            new WebDriverClickAction($this->mouse, $element)
        );

        return $this;
    }

    /**
     * Mouse click and hold.
     * If $element is provided, move to the middle of the element first.
     *
     * @param WebDriverElement $element
     * @return WebDriverActions
     */
    public function clickAndHold(WebDriverElement $element = null)
    {
        $this->action->addAction(
            new WebDriverClickAndHoldAction($this->mouse, $element)
        );

        return $this;
    }

    /**
     * Context-click (right click).
     * If $element is provided, move to the middle of the element first.
     *
     * @param WebDriverElement $element
     * @return WebDriverActions
     */
    public function contextClick(WebDriverElement $element = null)
    {
        $this->action->addAction(
            new WebDriverContextClickAction($this->mouse, $element)
        );

        return $this;
    }

    /**
     * Double click.
     * If $element is provided, move to the middle of the element first.
     *
     * @param WebDriverElement $element
     * @return WebDriverActions
     */
    public function doubleClick(WebDriverElement $element = null)
    {
        $this->action->addAction(
            new WebDriverDoubleClickAction($this->mouse, $element)
        );

        return $this;
    }

    /**
     * Drag and drop from $source to $target.
     *
     * @param WebDriverElement $source
     * @param WebDriverElement $target
     * @return WebDriverActions
     */
    public function dragAndDrop(WebDriverElement $source, WebDriverElement $target)
    {
        $this->action->addAction(
            new WebDriverClickAndHoldAction($this->mouse, $source)
        );
        $this->action->addAction(
            new WebDriverMouseMoveAction($this->mouse, $target)
        );
        $this->action->addAction(
            new WebDriverButtonReleaseAction($this->mouse, $target)
        );

        return $this;
    }

    /**
     * Drag $source and drop by offset ($x_offset, $y_offset).
     *
     * @param WebDriverElement $source
     * @param int $x_offset
     * @param int $y_offset
     * @return WebDriverActions
     */
    public function dragAndDropBy(WebDriverElement $source, $x_offset, $y_offset)
    {
        $this->action->addAction(
            new WebDriverClickAndHoldAction($this->mouse, $source)
        );
        $this->action->addAction(
            new WebDriverMoveToOffsetAction($this->mouse, null, $x_offset, $y_offset)
        );
        $this->action->addAction(
            new WebDriverButtonReleaseAction($this->mouse, null)
        );

        return $this;
    }

    /**
     * Mouse move by offset.
     *
     * @param int $x_offset
     * @param int $y_offset
     * @return WebDriverActions
     */
    public function moveByOffset($x_offset, $y_offset)
    {
        $this->action->addAction(
            new WebDriverMoveToOffsetAction($this->mouse, null, $x_offset, $y_offset)
        );

        return $this;
    }

    /**
     * Move to the middle of the given WebDriverElement.
     * Extra shift, calculated from the top-left corner of the element, can be set by passing $x_offset and $y_offset
     * parameters.
     *
     * @param WebDriverElement $element
     * @param int $x_offset
     * @param int $y_offset
     * @return WebDriverActions
     */
    public function moveToElement(WebDriverElement $element, $x_offset = null, $y_offset = null)
    {
        $this->action->addAction(new WebDriverMoveToOffsetAction(
            $this->mouse,
            $element,
            $x_offset,
            $y_offset
        ));

        return $this;
    }

    /**
     * Release the mouse button.
     * If $element is provided, move to the middle of the element first.
     *
     * @param WebDriverElement $element
     * @return WebDriverActions
     */
    public function release(WebDriverElement $element = null)
    {
        $this->action->addAction(
            new WebDriverButtonReleaseAction($this->mouse, $element)
        );

        return $this;
    }

    /**
     * Press a key on keyboard.
     * If $element is provided, focus on that element first.
     *
     * @see WebDriverKeys for special keys like CONTROL, ALT, etc.
     * @param WebDriverElement $element
     * @param string $key
     * @return WebDriverActions
     */
    public function keyDown(WebDriverElement $element = null, $key = null)
    {
        $this->action->addAction(
            new WebDriverKeyDownAction($this->keyboard, $this->mouse, $element, $key)
        );

        return $this;
    }

    /**
     * Release a key on keyboard.
     * If $element is provided, focus on that element first.
     *
     * @see WebDriverKeys for special keys like CONTROL, ALT, etc.
     * @param WebDriverElement $element
     * @param string $key
     * @return WebDriverActions
     */
    public function keyUp(WebDriverElement $element = null, $key = null)
    {
        $this->action->addAction(
            new WebDriverKeyUpAction($this->keyboard, $this->mouse, $element, $key)
        );

        return $this;
    }

    /**
     * Send keys by keyboard.
     * If $element is provided, focus on that element first (using single mouse click).
     *
     * @see WebDriverKeys for special keys like CONTROL, ALT, etc.
     * @param WebDriverElement $element
     * @param string $keys
     * @return WebDriverActions
     */
    public function sendKeys(WebDriverElement $element = null, $keys = null)
    {
        $this->action->addAction(
            new WebDriverSendKeysAction(
                $this->keyboard,
                $this->mouse,
                $element,
                $keys
            )
        );

        return $this;
    }
}
