<?php

namespace Facebook\WebDriver;

/**
 * An abstraction allowing the driver to access the browser's history and to
 * navigate to a given URL.
 */
interface WebDriverNavigationInterface
{
    /**
     * Move back a single entry in the browser's history, if possible.
     * This is equivalent to pressing the back button in the browser or invoking window.history.back.
     *
     * @return self
     */
    public function back();

    /**
     * Move forward a single entry in the browser's history, if possible.
     * This is equivalent to pressing the forward button in the browser or invoking window.history.back.
     *
     * @return self
     */
    public function forward();

    /**
     * Refresh the current page
     * This is equivalent to pressing the refresh button in the browser.
     *
     * @return self
     */
    public function refresh();

    /**
     * Navigate to the given URL
     *
     * @param string $url
     * @return self
     * @see WebDriver::get()
     */
    public function to($url);
}
