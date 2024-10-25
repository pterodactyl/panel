<?php

namespace Facebook\WebDriver\Support;

use Facebook\WebDriver\Remote\RemoteExecuteMethod;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\Remote\WebDriverBrowserType;

/**
 * Certain drivers have decided to not provide the endpoint which determines element displayedness, because
 * the W3C WebDriver specification no longer dictates it.
 *
 * In those instances, we determine this using a script ("atom").
 *
 * @see https://w3c.github.io/webdriver/#element-displayedness
 *
 * Also note in case more than this one atom is used, this logic here should be refactored to some AbstractAtom.
 */
class IsElementDisplayedAtom
{
    /**
     * List of browsers which are known to support /displayed endpoint on their own (so they don't need this atom).
     *
     * @var array
     */
    const BROWSERS_WITH_ENDPOINT_SUPPORT = [
        WebDriverBrowserType::CHROME,
        WebDriverBrowserType::FIREFOX,
        WebDriverBrowserType::MICROSOFT_EDGE,
    ];

    /**
     * @var RemoteWebDriver
     */
    private $driver;

    public function __construct(RemoteWebDriver $driver)
    {
        $this->driver = $driver;
    }

    public static function match($browserName)
    {
        return !in_array($browserName, self::BROWSERS_WITH_ENDPOINT_SUPPORT, true);
    }

    public function execute($params)
    {
        $element = new RemoteWebElement(
            new RemoteExecuteMethod($this->driver),
            $params[':id'],
            $this->driver->isW3cCompliant()
        );

        return $this->executeAtom('isElementDisplayed', $element);
    }

    protected function executeAtom($atomName, ...$params)
    {
        return $this->driver->executeScript(
            sprintf('%s; return (%s).apply(null, arguments);', $this->loadAtomScript($atomName), $atomName),
            $params
        );
    }

    private function loadAtomScript($atomName)
    {
        return file_get_contents(__DIR__ . '/../scripts/' . $atomName . '.js');
    }
}
