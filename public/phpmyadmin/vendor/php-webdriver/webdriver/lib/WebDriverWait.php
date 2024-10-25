<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;

/**
 * A utility class, designed to help the user to wait until a condition turns true.
 *
 * @see WebDriverExpectedCondition.
 */
class WebDriverWait
{
    /**
     * @var WebDriver
     */
    protected $driver;
    /**
     * @var int
     */
    protected $timeout;
    /**
     * @var int
     */
    protected $interval;

    public function __construct(WebDriver $driver, $timeout_in_second = null, $interval_in_millisecond = null)
    {
        $this->driver = $driver;
        $this->timeout = isset($timeout_in_second) ? $timeout_in_second : 30;
        $this->interval = $interval_in_millisecond ?: 250;
    }

    /**
     * Calls the function provided with the driver as an argument until the return value is not falsey.
     *
     * @param callable|WebDriverExpectedCondition $func_or_ec
     * @param string $message
     *
     * @throws \Exception
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return mixed The return value of $func_or_ec
     */
    public function until($func_or_ec, $message = '')
    {
        $end = microtime(true) + $this->timeout;
        $last_exception = null;

        while ($end > microtime(true)) {
            try {
                if ($func_or_ec instanceof WebDriverExpectedCondition) {
                    $ret_val = call_user_func($func_or_ec->getApply(), $this->driver);
                } else {
                    $ret_val = call_user_func($func_or_ec, $this->driver);
                }
                if ($ret_val) {
                    return $ret_val;
                }
            } catch (NoSuchElementException $e) {
                $last_exception = $e;
            }
            usleep($this->interval * 1000);
        }

        if ($last_exception) {
            throw $last_exception;
        }

        throw new TimeoutException($message);
    }
}
