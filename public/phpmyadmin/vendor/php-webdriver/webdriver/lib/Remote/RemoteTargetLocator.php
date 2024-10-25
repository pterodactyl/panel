<?php

namespace Facebook\WebDriver\Remote;

use Facebook\WebDriver\Exception\UnsupportedOperationException;
use Facebook\WebDriver\WebDriverAlert;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverTargetLocator;

/**
 * Used to locate a given frame or window for RemoteWebDriver.
 */
class RemoteTargetLocator implements WebDriverTargetLocator
{
    /** @var RemoteExecuteMethod */
    protected $executor;
    /** @var RemoteWebDriver */
    protected $driver;
    /** @var bool */
    protected $isW3cCompliant;

    public function __construct(RemoteExecuteMethod $executor, RemoteWebDriver $driver, $isW3cCompliant = false)
    {
        $this->executor = $executor;
        $this->driver = $driver;
        $this->isW3cCompliant = $isW3cCompliant;
    }

    /**
     * @return RemoteWebDriver
     */
    public function defaultContent()
    {
        $params = ['id' => null];
        $this->executor->execute(DriverCommand::SWITCH_TO_FRAME, $params);

        return $this->driver;
    }

    /**
     * @param WebDriverElement|null|int|string $frame The WebDriverElement, the id or the name of the frame.
     * When null, switch to the current top-level browsing context When int, switch to the WindowProxy identified
     * by the value. When an Element, switch to that Element.
     * @return RemoteWebDriver
     */
    public function frame($frame)
    {
        if ($this->isW3cCompliant) {
            if ($frame instanceof WebDriverElement) {
                $id = [JsonWireCompat::WEB_DRIVER_ELEMENT_IDENTIFIER => $frame->getID()];
            } elseif ($frame === null) {
                $id = null;
            } elseif (is_int($frame)) {
                $id = $frame;
            } else {
                throw new \InvalidArgumentException(
                    'In W3C compliance mode frame must be either instance of WebDriverElement, integer or null'
                );
            }
        } else {
            if ($frame instanceof WebDriverElement) {
                $id = ['ELEMENT' => $frame->getID()];
            } elseif ($frame === null) {
                $id = null;
            } elseif (is_int($frame)) {
                $id = $frame;
            } else {
                $id = (string) $frame;
            }
        }

        $params = ['id' => $id];
        $this->executor->execute(DriverCommand::SWITCH_TO_FRAME, $params);

        return $this->driver;
    }

    /**
     * Switch to the parent iframe.
     *
     * @return RemoteWebDriver This driver focused on the parent frame
     */
    public function parent()
    {
        $this->executor->execute(DriverCommand::SWITCH_TO_PARENT_FRAME, []);

        return $this->driver;
    }

    /**
     * @param string $handle The handle of the window to be focused on.
     * @return RemoteWebDriver
     */
    public function window($handle)
    {
        if ($this->isW3cCompliant) {
            $params = ['handle' => (string) $handle];
        } else {
            $params = ['name' => (string) $handle];
        }

        $this->executor->execute(DriverCommand::SWITCH_TO_WINDOW, $params);

        return $this->driver;
    }

    /**
     * Creates a new browser window and switches the focus for future commands of this driver to the new window.
     *
     * @see https://w3c.github.io/webdriver/#new-window
     * @param string $windowType The type of a new browser window that should be created. One of [tab, window].
     * The created window is not guaranteed to be of the requested type; if the driver does not support the requested
     * type, a new browser window will be created of whatever type the driver does support.
     * @throws UnsupportedOperationException
     * @return RemoteWebDriver This driver focused on the given window
     */
    public function newWindow($windowType = self::WINDOW_TYPE_TAB)
    {
        if ($windowType !== self::WINDOW_TYPE_TAB && $windowType !== self::WINDOW_TYPE_WINDOW) {
            throw new \InvalidArgumentException('Window type must by either "tab" or "window"');
        }

        if (!$this->isW3cCompliant) {
            throw new UnsupportedOperationException('New window is only supported in W3C mode');
        }

        $response = $this->executor->execute(DriverCommand::NEW_WINDOW, ['type' => $windowType]);

        $this->window($response['handle']);

        return $this->driver;
    }

    public function alert()
    {
        return new WebDriverAlert($this->executor);
    }

    /**
     * @return RemoteWebElement
     */
    public function activeElement()
    {
        $response = $this->driver->execute(DriverCommand::GET_ACTIVE_ELEMENT, []);
        $method = new RemoteExecuteMethod($this->driver);

        return new RemoteWebElement($method, JsonWireCompat::getElement($response), $this->isW3cCompliant);
    }
}
