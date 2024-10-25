<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Support\Events\EventFiringWebDriver;

class WebDriverDispatcher
{
    /**
     * @var array
     */
    protected $listeners = [];
    /**
     * @var EventFiringWebDriver
     */
    protected $driver;

    /**
     * this is needed so that EventFiringWebElement can pass the driver to the
     * exception handling
     *
     * @param EventFiringWebDriver $driver
     * @return $this
     */
    public function setDefaultDriver(EventFiringWebDriver $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @return null|EventFiringWebDriver
     */
    public function getDefaultDriver()
    {
        return $this->driver;
    }

    /**
     * @param WebDriverEventListener $listener
     * @return $this
     */
    public function register(WebDriverEventListener $listener)
    {
        $this->listeners[] = $listener;

        return $this;
    }

    /**
     * @param WebDriverEventListener $listener
     * @return $this
     */
    public function unregister(WebDriverEventListener $listener)
    {
        $key = array_search($listener, $this->listeners, true);
        if ($key !== false) {
            unset($this->listeners[$key]);
        }

        return $this;
    }

    /**
     * @param mixed $method
     * @param mixed $arguments
     * @return $this
     */
    public function dispatch($method, $arguments)
    {
        foreach ($this->listeners as $listener) {
            call_user_func_array([$listener, $method], $arguments);
        }

        return $this;
    }
}
