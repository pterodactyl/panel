<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Interactions\Touch\WebDriverTouchScreen;

/**
 * The interface for WebDriver.
 */
interface WebDriver extends WebDriverSearchContext
{
    /**
     * Close the current window.
     *
     * @return WebDriver The current instance.
     */
    public function close();

    /**
     * Load a new web page in the current browser window.
     *
     * @param string $url
     * @return WebDriver The current instance.
     */
    public function get($url);

    /**
     * Get a string representing the current URL that the browser is looking at.
     *
     * @return string The current URL.
     */
    public function getCurrentURL();

    /**
     * Get the source of the last loaded page.
     *
     * @return string The current page source.
     */
    public function getPageSource();

    /**
     * Get the title of the current page.
     *
     * @return string The title of the current page.
     */
    public function getTitle();

    /**
     * Return an opaque handle to this window that uniquely identifies it within
     * this driver instance.
     *
     * @return string The current window handle.
     */
    public function getWindowHandle();

    /**
     * Get all window handles available to the current session.
     *
     * @return array An array of string containing all available window handles.
     */
    public function getWindowHandles();

    /**
     * Quits this driver, closing every associated window.
     */
    public function quit();

    /**
     * Take a screenshot of the current page.
     *
     * @param string $save_as The path of the screenshot to be saved.
     * @return string The screenshot in PNG format.
     */
    public function takeScreenshot($save_as = null);

    /**
     * Construct a new WebDriverWait by the current WebDriver instance.
     * Sample usage:
     *
     *   $driver->wait(20, 1000)->until(
     *     WebDriverExpectedCondition::titleIs('WebDriver Page')
     *   );
     *
     * @param int $timeout_in_second
     * @param int $interval_in_millisecond
     * @return WebDriverWait
     */
    public function wait(
        $timeout_in_second = 30,
        $interval_in_millisecond = 250
    );

    /**
     * An abstraction for managing stuff you would do in a browser menu. For
     * example, adding and deleting cookies.
     *
     * @return WebDriverOptions
     */
    public function manage();

    /**
     * An abstraction allowing the driver to access the browser's history and to
     * navigate to a given URL.
     *
     * @return WebDriverNavigationInterface
     * @see WebDriverNavigation
     */
    public function navigate();

    /**
     * Switch to a different window or frame.
     *
     * @return WebDriverTargetLocator
     * @see WebDriverTargetLocator
     */
    public function switchTo();

    // TODO: Add in next major release (BC)
    ///**
    // * @return WebDriverTouchScreen
    // */
    //public function getTouch();

    /**
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public function execute($name, $params);

    // TODO: Add in next major release (BC)
    ///**
    // * Execute custom commands on remote end.
    // * For example vendor-specific commands or other commands not implemented by php-webdriver.
    // *
    // * @see https://github.com/php-webdriver/php-webdriver/wiki/Custom-commands
    // * @param string $endpointUrl
    // * @param string $method
    // * @param array $params
    // * @return mixed|null
    // */
    //public function executeCustomCommand($endpointUrl, $method = 'GET', $params = []);
}
