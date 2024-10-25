<?php

namespace Facebook\WebDriver;

interface WebDriverCapabilities
{
    /**
     * @return string The name of the browser.
     */
    public function getBrowserName();

    /**
     * @param string $name
     * @return mixed The value of a capability.
     */
    public function getCapability($name);

    /**
     * @return string The name of the platform.
     */
    public function getPlatform();

    /**
     * @return string The version of the browser.
     */
    public function getVersion();

    /**
     * @param string $capability_name
     * @return bool Whether the value is not null and not false.
     */
    public function is($capability_name);

    /**
     * @todo Remove in next major release (BC)
     * @deprecated All browsers are always JS enabled except HtmlUnit and it's not meaningful to disable JS execution.
     * @return bool Whether javascript is enabled.
     */
    public function isJavascriptEnabled();

    // TODO: Add in next major release (BC)
    ///**
    // * @return array
    // */
    //public function toArray();
}
