<?php

namespace Facebook\WebDriver;

/**
 * Used to locate a given frame or window.
 */
interface WebDriverTargetLocator
{
    /** @var string */
    const WINDOW_TYPE_WINDOW = 'window';
    /** @var string */
    const WINDOW_TYPE_TAB = 'tab';

    /**
     * Set the current browsing context to the current top-level browsing context.
     * This is the same as calling `RemoteTargetLocator::frame(null);`
     *
     * @return WebDriver The driver focused on the top window or the first frame.
     */
    public function defaultContent();

    /**
     * Switch to the iframe by its id or name.
     *
     * @param WebDriverElement|null|int|string $frame The WebDriverElement, the id or the name of the frame.
     * When null, switch to the current top-level browsing context When int, switch to the WindowProxy identified
     * by the value. When an Element, switch to that Element.
     *
     * @throws \InvalidArgumentException
     * @return WebDriver The driver focused on the given frame.
     */
    public function frame($frame);

    // TODO: Add in next major release (BC)
    ///**
    // * Switch to the parent iframe.
    // *
    // * @return WebDriver This driver focused on the parent frame
    // */
    //public function parent();

    /**
     * Switch the focus to another window by its handle.
     *
     * @param string $handle The handle of the window to be focused on.
     * @return WebDriver The driver focused on the given window.
     * @see WebDriver::getWindowHandles
     */
    public function window($handle);

    // TODO: Add in next major release (BC)
    //public function newWindow($windowType = self::WINDOW_TYPE_TAB);

    /**
     * Switch to the currently active modal dialog for this particular driver instance.
     *
     * @return WebDriverAlert
     */
    public function alert();

    /**
     * Switches to the element that currently has focus within the document currently "switched to",
     * or the body element if this cannot be detected.
     *
     * @return WebDriverElement
     */
    public function activeElement();
}
