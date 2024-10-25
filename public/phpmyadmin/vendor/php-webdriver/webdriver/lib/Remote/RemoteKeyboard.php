<?php

namespace Facebook\WebDriver\Remote;

use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverKeyboard;
use Facebook\WebDriver\WebDriverKeys;

/**
 * Execute keyboard commands for RemoteWebDriver.
 */
class RemoteKeyboard implements WebDriverKeyboard
{
    /** @var RemoteExecuteMethod */
    private $executor;
    /** @var WebDriver */
    private $driver;
    /** @var bool */
    private $isW3cCompliant;

    /**
     * @param bool $isW3cCompliant
     */
    public function __construct(RemoteExecuteMethod $executor, WebDriver $driver, $isW3cCompliant = false)
    {
        $this->executor = $executor;
        $this->driver = $driver;
        $this->isW3cCompliant = $isW3cCompliant;
    }

    /**
     * Send keys to active element
     * @param string|array $keys
     * @return $this
     */
    public function sendKeys($keys)
    {
        if ($this->isW3cCompliant) {
            $activeElement = $this->driver->switchTo()->activeElement();
            $activeElement->sendKeys($keys);
        } else {
            $this->executor->execute(DriverCommand::SEND_KEYS_TO_ACTIVE_ELEMENT, [
                'value' => WebDriverKeys::encode($keys),
            ]);
        }

        return $this;
    }

    /**
     * Press a modifier key
     *
     * @see WebDriverKeys
     * @param string $key
     * @return $this
     */
    public function pressKey($key)
    {
        if ($this->isW3cCompliant) {
            $this->executor->execute(DriverCommand::ACTIONS, [
                'actions' => [
                    [
                        'type' => 'key',
                        'id' => 'keyboard',
                        'actions' => [['type' => 'keyDown', 'value' => $key]],
                    ],
                ],
            ]);
        } else {
            $this->executor->execute(DriverCommand::SEND_KEYS_TO_ACTIVE_ELEMENT, [
                'value' => [(string) $key],
            ]);
        }

        return $this;
    }

    /**
     * Release a modifier key
     *
     * @see WebDriverKeys
     * @param string $key
     * @return $this
     */
    public function releaseKey($key)
    {
        if ($this->isW3cCompliant) {
            $this->executor->execute(DriverCommand::ACTIONS, [
                'actions' => [
                    [
                        'type' => 'key',
                        'id' => 'keyboard',
                        'actions' => [['type' => 'keyUp', 'value' => $key]],
                    ],
                ],
            ]);
        } else {
            $this->executor->execute(DriverCommand::SEND_KEYS_TO_ACTIVE_ELEMENT, [
                'value' => [(string) $key],
            ]);
        }

        return $this;
    }
}
