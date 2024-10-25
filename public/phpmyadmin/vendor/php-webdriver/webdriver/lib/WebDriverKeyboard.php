<?php

namespace Facebook\WebDriver;

interface WebDriverKeyboard
{
    /**
     * Send a sequence of keys.
     *
     * @param string $keys
     * @return $this
     */
    public function sendKeys($keys);

    /**
     * Press a key
     *
     * @see WebDriverKeys
     * @param string $key
     * @return $this
     */
    public function pressKey($key);

    /**
     * Release a key
     *
     * @see WebDriverKeys
     * @param string $key
     * @return $this
     */
    public function releaseKey($key);
}
