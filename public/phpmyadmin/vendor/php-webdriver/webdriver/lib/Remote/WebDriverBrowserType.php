<?php

namespace Facebook\WebDriver\Remote;

/**
 * All the browsers supported by selenium.
 *
 * @codeCoverageIgnore
 */
class WebDriverBrowserType
{
    const FIREFOX = 'firefox';
    const FIREFOX_PROXY = 'firefoxproxy';
    const FIREFOX_CHROME = 'firefoxchrome';
    const GOOGLECHROME = 'googlechrome';
    const SAFARI = 'safari';
    const SAFARI_PROXY = 'safariproxy';
    const OPERA = 'opera';
    const MICROSOFT_EDGE = 'MicrosoftEdge';
    const IEXPLORE = 'iexplore';
    const IEXPLORE_PROXY = 'iexploreproxy';
    const CHROME = 'chrome';
    const KONQUEROR = 'konqueror';
    const MOCK = 'mock';
    const IE_HTA = 'iehta';
    const ANDROID = 'android';
    const HTMLUNIT = 'htmlunit';
    const IE = 'internet explorer';
    const IPHONE = 'iphone';
    const IPAD = 'iPad';
    /**
     * @deprecated PhantomJS is no longer developed and its support will be removed in next major version.
     * Use headless Chrome or Firefox instead.
     */
    const PHANTOMJS = 'phantomjs';

    private function __construct()
    {
    }
}
