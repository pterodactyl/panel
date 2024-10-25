<?php

namespace Facebook\WebDriver\Remote;

/**
 * WebDriverCapabilityType contains all constants defined in the WebDriver Wire Protocol.
 *
 * @codeCoverageIgnore
 */
class WebDriverCapabilityType
{
    const BROWSER_NAME = 'browserName';
    const VERSION = 'version';
    const PLATFORM = 'platform';
    const JAVASCRIPT_ENABLED = 'javascriptEnabled';
    const TAKES_SCREENSHOT = 'takesScreenshot';
    const HANDLES_ALERTS = 'handlesAlerts';
    const DATABASE_ENABLED = 'databaseEnabled';
    const LOCATION_CONTEXT_ENABLED = 'locationContextEnabled';
    const APPLICATION_CACHE_ENABLED = 'applicationCacheEnabled';
    const BROWSER_CONNECTION_ENABLED = 'browserConnectionEnabled';
    const CSS_SELECTORS_ENABLED = 'cssSelectorsEnabled';
    const WEB_STORAGE_ENABLED = 'webStorageEnabled';
    const ROTATABLE = 'rotatable';
    const ACCEPT_SSL_CERTS = 'acceptSslCerts';
    const NATIVE_EVENTS = 'nativeEvents';
    const PROXY = 'proxy';

    private function __construct()
    {
    }
}
