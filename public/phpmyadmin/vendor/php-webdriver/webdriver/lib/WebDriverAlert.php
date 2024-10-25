<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\ExecuteMethod;

/**
 * An abstraction allowing the driver to manipulate the javascript alerts
 */
class WebDriverAlert
{
    /**
     * @var ExecuteMethod
     */
    protected $executor;

    public function __construct(ExecuteMethod $executor)
    {
        $this->executor = $executor;
    }

    /**
     * Accept alert
     *
     * @return WebDriverAlert The instance.
     */
    public function accept()
    {
        $this->executor->execute(DriverCommand::ACCEPT_ALERT);

        return $this;
    }

    /**
     * Dismiss alert
     *
     * @return WebDriverAlert The instance.
     */
    public function dismiss()
    {
        $this->executor->execute(DriverCommand::DISMISS_ALERT);

        return $this;
    }

    /**
     * Get alert text
     *
     * @return string
     */
    public function getText()
    {
        return $this->executor->execute(DriverCommand::GET_ALERT_TEXT);
    }

    /**
     * Send keystrokes to javascript prompt() dialog
     *
     * @param string $value
     * @return WebDriverAlert
     */
    public function sendKeys($value)
    {
        $this->executor->execute(
            DriverCommand::SET_ALERT_VALUE,
            ['text' => $value]
        );

        return $this;
    }
}
