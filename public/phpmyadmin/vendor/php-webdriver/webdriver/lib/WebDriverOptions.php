<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Exception\NoSuchCookieException;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\ExecuteMethod;
use InvalidArgumentException;

/**
 * Managing stuff you would do in a browser.
 */
class WebDriverOptions
{
    /**
     * @var ExecuteMethod
     */
    protected $executor;
    /**
     * @var bool
     */
    protected $isW3cCompliant;

    public function __construct(ExecuteMethod $executor, $isW3cCompliant = false)
    {
        $this->executor = $executor;
        $this->isW3cCompliant = $isW3cCompliant;
    }

    /**
     * Add a specific cookie.
     *
     * @see Cookie for description of possible cookie properties
     * @param Cookie|array $cookie Cookie object. May be also created from array for compatibility reasons.
     * @return WebDriverOptions The current instance.
     */
    public function addCookie($cookie)
    {
        if (is_array($cookie)) { // @todo @deprecated remove in 2.0
            $cookie = Cookie::createFromArray($cookie);
        }
        if (!$cookie instanceof Cookie) {
            throw new InvalidArgumentException('Cookie must be set from instance of Cookie class or from array.');
        }

        $this->executor->execute(
            DriverCommand::ADD_COOKIE,
            ['cookie' => $cookie->toArray()]
        );

        return $this;
    }

    /**
     * Delete all the cookies that are currently visible.
     *
     * @return WebDriverOptions The current instance.
     */
    public function deleteAllCookies()
    {
        $this->executor->execute(DriverCommand::DELETE_ALL_COOKIES);

        return $this;
    }

    /**
     * Delete the cookie with the given name.
     *
     * @param string $name
     * @return WebDriverOptions The current instance.
     */
    public function deleteCookieNamed($name)
    {
        $this->executor->execute(
            DriverCommand::DELETE_COOKIE,
            [':name' => $name]
        );

        return $this;
    }

    /**
     * Get the cookie with a given name.
     *
     * @param string $name
     * @throws NoSuchCookieException In W3C compliant mode if no cookie with the given name is present
     * @return Cookie|null The cookie, or null in JsonWire mode if no cookie with the given name is present
     */
    public function getCookieNamed($name)
    {
        if ($this->isW3cCompliant) {
            $cookieArray = $this->executor->execute(
                DriverCommand::GET_NAMED_COOKIE,
                [':name' => $name]
            );

            if (!is_array($cookieArray)) { // Microsoft Edge returns null even in W3C mode => emulate proper behavior
                throw new NoSuchCookieException('no such cookie');
            }

            return Cookie::createFromArray($cookieArray);
        }

        $cookies = $this->getCookies();
        foreach ($cookies as $cookie) {
            if ($cookie['name'] === $name) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * Get all the cookies for the current domain.
     *
     * @return Cookie[] The array of cookies presented.
     */
    public function getCookies()
    {
        $cookieArrays = $this->executor->execute(DriverCommand::GET_ALL_COOKIES);
        if (!is_array($cookieArrays)) { // Microsoft Edge returns null if there are no cookies...
            return [];
        }

        $cookies = [];
        foreach ($cookieArrays as $cookieArray) {
            $cookies[] = Cookie::createFromArray($cookieArray);
        }

        return $cookies;
    }

    /**
     * Return the interface for managing driver timeouts.
     *
     * @return WebDriverTimeouts
     */
    public function timeouts()
    {
        return new WebDriverTimeouts($this->executor, $this->isW3cCompliant);
    }

    /**
     * An abstraction allowing the driver to manipulate the browser's window
     *
     * @return WebDriverWindow
     * @see WebDriverWindow
     */
    public function window()
    {
        return new WebDriverWindow($this->executor, $this->isW3cCompliant);
    }

    /**
     * Get the log for a given log type. Log buffer is reset after each request.
     *
     * @param string $log_type The log type.
     * @return array The list of log entries.
     * @see https://github.com/SeleniumHQ/selenium/wiki/JsonWireProtocol#log-type
     */
    public function getLog($log_type)
    {
        return $this->executor->execute(
            DriverCommand::GET_LOG,
            ['type' => $log_type]
        );
    }

    /**
     * Get available log types.
     *
     * @return array The list of available log types.
     * @see https://github.com/SeleniumHQ/selenium/wiki/JsonWireProtocol#log-type
     */
    public function getAvailableLogTypes()
    {
        return $this->executor->execute(DriverCommand::GET_AVAILABLE_LOG_TYPES);
    }
}
