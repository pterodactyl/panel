<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Support\Events\EventFiringWebDriver;
use Facebook\WebDriver\Support\Events\EventFiringWebElement;

interface WebDriverEventListener
{
    /**
     * @param string $url
     * @param EventFiringWebDriver $driver
     */
    public function beforeNavigateTo($url, EventFiringWebDriver $driver);

    /**
     * @param string $url
     * @param EventFiringWebDriver $driver
     */
    public function afterNavigateTo($url, EventFiringWebDriver $driver);

    /**
     * @param EventFiringWebDriver $driver
     */
    public function beforeNavigateBack(EventFiringWebDriver $driver);

    /**
     * @param EventFiringWebDriver $driver
     */
    public function afterNavigateBack(EventFiringWebDriver $driver);

    /**
     * @param EventFiringWebDriver $driver
     */
    public function beforeNavigateForward(EventFiringWebDriver $driver);

    /**
     * @param EventFiringWebDriver $driver
     */
    public function afterNavigateForward(EventFiringWebDriver $driver);

    /**
     * @param WebDriverBy $by
     * @param EventFiringWebElement|null $element
     * @param EventFiringWebDriver $driver
     */
    public function beforeFindBy(WebDriverBy $by, $element, EventFiringWebDriver $driver);

    /**
     * @param WebDriverBy $by
     * @param EventFiringWebElement|null $element
     * @param EventFiringWebDriver $driver
     */
    public function afterFindBy(WebDriverBy $by, $element, EventFiringWebDriver $driver);

    /**
     * @param string $script
     * @param EventFiringWebDriver $driver
     */
    public function beforeScript($script, EventFiringWebDriver $driver);

    /**
     * @param string $script
     * @param EventFiringWebDriver $driver
     */
    public function afterScript($script, EventFiringWebDriver $driver);

    /**
     * @param EventFiringWebElement $element
     */
    public function beforeClickOn(EventFiringWebElement $element);

    /**
     * @param EventFiringWebElement $element
     */
    public function afterClickOn(EventFiringWebElement $element);

    /**
     * @param EventFiringWebElement $element
     */
    public function beforeChangeValueOf(EventFiringWebElement $element);

    /**
     * @param EventFiringWebElement $element
     */
    public function afterChangeValueOf(EventFiringWebElement $element);

    /**
     * @param WebDriverException $exception
     * @param EventFiringWebDriver $driver
     */
    public function onException(WebDriverException $exception, EventFiringWebDriver $driver = null);
}
