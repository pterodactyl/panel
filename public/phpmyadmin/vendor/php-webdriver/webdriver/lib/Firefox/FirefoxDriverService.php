<?php

namespace Facebook\WebDriver\Firefox;

use Facebook\WebDriver\Remote\Service\DriverService;

class FirefoxDriverService extends DriverService
{
    /**
     * @var string Name of the environment variable storing the path to the driver binary
     */
    const WEBDRIVER_FIREFOX_DRIVER = 'WEBDRIVER_FIREFOX_DRIVER';
    /**
     * @var string Default executable used when no other is provided
     * @internal
     */
    const DEFAULT_EXECUTABLE = 'geckodriver';

    /**
     * @return static
     */
    public static function createDefaultService()
    {
        $pathToExecutable = getenv(static::WEBDRIVER_FIREFOX_DRIVER);
        if ($pathToExecutable === false || $pathToExecutable === '') {
            $pathToExecutable = static::DEFAULT_EXECUTABLE;
        }

        $port = 9515; // TODO: Get another free port if the default port is used.
        $args = ['-p=' . $port];

        return new static($pathToExecutable, $port, $args);
    }
}
