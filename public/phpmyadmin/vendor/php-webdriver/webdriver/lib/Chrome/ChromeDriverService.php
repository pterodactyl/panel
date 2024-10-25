<?php

namespace Facebook\WebDriver\Chrome;

use Facebook\WebDriver\Remote\Service\DriverService;

class ChromeDriverService extends DriverService
{
    /**
     * The environment variable storing the path to the chrome driver executable.
     * @deprecated Use ChromeDriverService::CHROME_DRIVER_EXECUTABLE
     */
    const CHROME_DRIVER_EXE_PROPERTY = 'webdriver.chrome.driver';
    /** @var string The environment variable storing the path to the chrome driver executable */
    const CHROME_DRIVER_EXECUTABLE = 'WEBDRIVER_CHROME_DRIVER';
    /**
     * @var string Default executable used when no other is provided
     * @internal
     */
    const DEFAULT_EXECUTABLE = 'chromedriver';

    /**
     * @return static
     */
    public static function createDefaultService()
    {
        $pathToExecutable = getenv(self::CHROME_DRIVER_EXECUTABLE) ?: getenv(self::CHROME_DRIVER_EXE_PROPERTY);
        if ($pathToExecutable === false || $pathToExecutable === '') {
            $pathToExecutable = static::DEFAULT_EXECUTABLE;
        }

        $port = 9515; // TODO: Get another port if the default port is used.
        $args = ['--port=' . $port];

        return new static($pathToExecutable, $port, $args);
    }
}
