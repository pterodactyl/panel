<?php

namespace Facebook\WebDriver\Exception;

/**
 * The driver server process is unexpectedly no longer available.
 */
class DriverServerDiedException extends WebDriverException
{
    public function __construct(\Exception $previous = null)
    {
        parent::__construct('The driver server has died.');
        \Exception::__construct($this->getMessage(), $this->getCode(), $previous);
    }
}
