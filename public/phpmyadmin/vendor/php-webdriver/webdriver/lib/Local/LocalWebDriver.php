<?php

namespace Facebook\WebDriver\Local;

use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * @todo Break inheritance from RemoteWebDriver in next major version. (Composition over inheritance!)
 */
abstract class LocalWebDriver extends RemoteWebDriver
{
    /**
     * @param string $selenium_server_url
     * @param null $desired_capabilities
     * @param null $connection_timeout_in_ms
     * @param null $request_timeout_in_ms
     * @param null $http_proxy
     * @param null $http_proxy_port
     * @param DesiredCapabilities|null $required_capabilities
     * @throws WebDriverException
     * @return RemoteWebDriver
     * @todo Remove in next major version (should not be inherited)
     */
    public static function create(
        $selenium_server_url = 'http://localhost:4444/wd/hub',
        $desired_capabilities = null,
        $connection_timeout_in_ms = null,
        $request_timeout_in_ms = null,
        $http_proxy = null,
        $http_proxy_port = null,
        DesiredCapabilities $required_capabilities = null
    ) {
        throw new WebDriverException('Use start() method to start local WebDriver.');
    }

    /**
     * @param string $session_id
     * @param string $selenium_server_url
     * @param null $connection_timeout_in_ms
     * @param null $request_timeout_in_ms
     * @throws WebDriverException
     * @return RemoteWebDriver
     * @todo Remove in next major version (should not be inherited)
     */
    public static function createBySessionID(
        $session_id,
        $selenium_server_url = 'http://localhost:4444/wd/hub',
        $connection_timeout_in_ms = null,
        $request_timeout_in_ms = null
    ) {
        throw new WebDriverException('Use start() method to start local WebDriver.');
    }
}
