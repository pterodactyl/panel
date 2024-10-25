<?php

namespace Facebook\WebDriver\Chrome;

use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Provide access to Chrome DevTools Protocol (CDP) commands via HTTP endpoint of Chromedriver.
 *
 * @see https://chromedevtools.github.io/devtools-protocol/
 */
class ChromeDevToolsDriver
{
    const SEND_COMMAND = [
        'method' => 'POST',
        'url' => '/session/:sessionId/goog/cdp/execute',
    ];

    /**
     * @var RemoteWebDriver
     */
    private $driver;

    public function __construct(RemoteWebDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Executes a Chrome DevTools command
     *
     * @param string $command The DevTools command to execute
     * @param array $parameters Optional parameters to the command
     * @return array The result of the command
     */
    public function execute($command, array $parameters = [])
    {
        $params = ['cmd' => $command, 'params' => (object) $parameters];

        return $this->driver->executeCustomCommand(
            self::SEND_COMMAND['url'],
            self::SEND_COMMAND['method'],
            $params
        );
    }
}
