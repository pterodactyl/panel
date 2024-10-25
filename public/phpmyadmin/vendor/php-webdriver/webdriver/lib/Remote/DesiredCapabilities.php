<?php

namespace Facebook\WebDriver\Remote;

use Exception;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\WebDriverCapabilities;
use Facebook\WebDriver\WebDriverPlatform;

class DesiredCapabilities implements WebDriverCapabilities
{
    /** @var array */
    private $capabilities;

    /** @var array */
    private static $ossToW3c = [
        WebDriverCapabilityType::PLATFORM => 'platformName',
        WebDriverCapabilityType::VERSION => 'browserVersion',
        WebDriverCapabilityType::ACCEPT_SSL_CERTS => 'acceptInsecureCerts',
        ChromeOptions::CAPABILITY => ChromeOptions::CAPABILITY_W3C,
    ];

    public function __construct(array $capabilities = [])
    {
        $this->capabilities = $capabilities;
    }

    public static function createFromW3cCapabilities(array $capabilities = [])
    {
        $w3cToOss = array_flip(self::$ossToW3c);

        foreach ($w3cToOss as $w3cCapability => $ossCapability) {
            // Copy W3C capabilities to OSS ones
            if (array_key_exists($w3cCapability, $capabilities)) {
                $capabilities[$ossCapability] = $capabilities[$w3cCapability];
            }
        }

        return new self($capabilities);
    }

    /**
     * @return string The name of the browser.
     */
    public function getBrowserName()
    {
        return $this->get(WebDriverCapabilityType::BROWSER_NAME, '');
    }

    /**
     * @param string $browser_name
     * @return DesiredCapabilities
     */
    public function setBrowserName($browser_name)
    {
        $this->set(WebDriverCapabilityType::BROWSER_NAME, $browser_name);

        return $this;
    }

    /**
     * @return string The version of the browser.
     */
    public function getVersion()
    {
        return $this->get(WebDriverCapabilityType::VERSION, '');
    }

    /**
     * @param string $version
     * @return DesiredCapabilities
     */
    public function setVersion($version)
    {
        $this->set(WebDriverCapabilityType::VERSION, $version);

        return $this;
    }

    /**
     * @param string $name
     * @return mixed The value of a capability.
     */
    public function getCapability($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return DesiredCapabilities
     */
    public function setCapability($name, $value)
    {
        // When setting 'moz:firefoxOptions' from an array and not from instance of FirefoxOptions, we must merge
        // it with default FirefoxOptions to keep previous behavior (where the default preferences were added
        // using FirefoxProfile, thus not overwritten by adding 'moz:firefoxOptions')
        // TODO: remove in next major version, once FirefoxOptions are only accepted as object instance and not as array
        if ($name === FirefoxOptions::CAPABILITY && is_array($value)) {
            $defaultOptions = (new FirefoxOptions())->toArray();
            $value = array_merge($defaultOptions, $value);
        }

        $this->set($name, $value);

        return $this;
    }

    /**
     * @return string The name of the platform.
     */
    public function getPlatform()
    {
        return $this->get(WebDriverCapabilityType::PLATFORM, '');
    }

    /**
     * @param string $platform
     * @return DesiredCapabilities
     */
    public function setPlatform($platform)
    {
        $this->set(WebDriverCapabilityType::PLATFORM, $platform);

        return $this;
    }

    /**
     * @param string $capability_name
     * @return bool Whether the value is not null and not false.
     */
    public function is($capability_name)
    {
        return (bool) $this->get($capability_name);
    }

    /**
     * @todo Remove in next major release (BC)
     * @deprecated All browsers are always JS enabled except HtmlUnit and it's not meaningful to disable JS execution.
     * @return bool Whether javascript is enabled.
     */
    public function isJavascriptEnabled()
    {
        return $this->get(WebDriverCapabilityType::JAVASCRIPT_ENABLED, false);
    }

    /**
     * This is a htmlUnit-only option.
     *
     * @param bool $enabled
     * @throws Exception
     * @return DesiredCapabilities
     * @see https://github.com/SeleniumHQ/selenium/wiki/DesiredCapabilities#read-write-capabilities
     */
    public function setJavascriptEnabled($enabled)
    {
        $browser = $this->getBrowserName();
        if ($browser && $browser !== WebDriverBrowserType::HTMLUNIT) {
            throw new Exception(
                'isJavascriptEnabled() is a htmlunit-only option. ' .
                'See https://github.com/SeleniumHQ/selenium/wiki/DesiredCapabilities#read-write-capabilities.'
            );
        }

        $this->set(WebDriverCapabilityType::JAVASCRIPT_ENABLED, $enabled);

        return $this;
    }

    /**
     * @todo Remove side-effects - not change eg. ChromeOptions::CAPABILITY from instance of ChromeOptions to an array
     * @return array
     */
    public function toArray()
    {
        if (isset($this->capabilities[ChromeOptions::CAPABILITY]) &&
            $this->capabilities[ChromeOptions::CAPABILITY] instanceof ChromeOptions
        ) {
            $this->capabilities[ChromeOptions::CAPABILITY] =
                $this->capabilities[ChromeOptions::CAPABILITY]->toArray();
        }

        if (isset($this->capabilities[FirefoxOptions::CAPABILITY]) &&
            $this->capabilities[FirefoxOptions::CAPABILITY] instanceof FirefoxOptions
        ) {
            $this->capabilities[FirefoxOptions::CAPABILITY] =
                $this->capabilities[FirefoxOptions::CAPABILITY]->toArray();
        }

        if (isset($this->capabilities[FirefoxDriver::PROFILE]) &&
            $this->capabilities[FirefoxDriver::PROFILE] instanceof FirefoxProfile
        ) {
            $this->capabilities[FirefoxDriver::PROFILE] =
                $this->capabilities[FirefoxDriver::PROFILE]->encode();
        }

        return $this->capabilities;
    }

    /**
     * @return array
     */
    public function toW3cCompatibleArray()
    {
        $allowedW3cCapabilities = [
            'browserName',
            'browserVersion',
            'platformName',
            'acceptInsecureCerts',
            'pageLoadStrategy',
            'proxy',
            'setWindowRect',
            'timeouts',
            'strictFileInteractability',
            'unhandledPromptBehavior',
        ];

        $ossCapabilities = $this->toArray();
        $w3cCapabilities = [];

        foreach ($ossCapabilities as $capabilityKey => $capabilityValue) {
            // Copy already W3C compatible capabilities
            if (in_array($capabilityKey, $allowedW3cCapabilities, true)) {
                $w3cCapabilities[$capabilityKey] = $capabilityValue;
            }

            // Convert capabilities with changed name
            if (array_key_exists($capabilityKey, self::$ossToW3c)) {
                if ($capabilityKey === WebDriverCapabilityType::PLATFORM) {
                    $w3cCapabilities[self::$ossToW3c[$capabilityKey]] = mb_strtolower($capabilityValue);

                    // Remove platformName if it is set to "any"
                    if ($w3cCapabilities[self::$ossToW3c[$capabilityKey]] === 'any') {
                        unset($w3cCapabilities[self::$ossToW3c[$capabilityKey]]);
                    }
                } else {
                    $w3cCapabilities[self::$ossToW3c[$capabilityKey]] = $capabilityValue;
                }
            }

            // Copy vendor extensions
            if (mb_strpos($capabilityKey, ':') !== false) {
                $w3cCapabilities[$capabilityKey] = $capabilityValue;
            }
        }

        // Convert ChromeOptions
        if (array_key_exists(ChromeOptions::CAPABILITY, $ossCapabilities)) {
            if (array_key_exists(ChromeOptions::CAPABILITY_W3C, $ossCapabilities)) {
                $w3cCapabilities[ChromeOptions::CAPABILITY_W3C] = new \ArrayObject(
                    array_merge_recursive(
                        (array) $ossCapabilities[ChromeOptions::CAPABILITY],
                        (array) $ossCapabilities[ChromeOptions::CAPABILITY_W3C]
                    )
                );
            } else {
                $w3cCapabilities[ChromeOptions::CAPABILITY_W3C] = $ossCapabilities[ChromeOptions::CAPABILITY];
            }
        }

        // Convert Firefox profile
        if (array_key_exists(FirefoxDriver::PROFILE, $ossCapabilities)) {
            // Convert profile only if not already set in moz:firefoxOptions
            if (!array_key_exists(FirefoxOptions::CAPABILITY, $ossCapabilities)
                || !array_key_exists('profile', $ossCapabilities[FirefoxOptions::CAPABILITY])) {
                $w3cCapabilities[FirefoxOptions::CAPABILITY]['profile'] = $ossCapabilities[FirefoxDriver::PROFILE];
            }
        }

        return $w3cCapabilities;
    }

    /**
     * @return static
     */
    public static function android()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::ANDROID,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::ANDROID,
        ]);
    }

    /**
     * @return static
     */
    public static function chrome()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::CHROME,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::ANY,
        ]);
    }

    /**
     * @return static
     */
    public static function firefox()
    {
        $caps = new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::FIREFOX,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::ANY,
        ]);

        $caps->setCapability(FirefoxOptions::CAPABILITY, new FirefoxOptions()); // to add default options

        return $caps;
    }

    /**
     * @return static
     */
    public static function htmlUnit()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::HTMLUNIT,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::ANY,
        ]);
    }

    /**
     * @return static
     */
    public static function htmlUnitWithJS()
    {
        $caps = new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::HTMLUNIT,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::ANY,
        ]);

        return $caps->setJavascriptEnabled(true);
    }

    /**
     * @return static
     */
    public static function internetExplorer()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::IE,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::WINDOWS,
        ]);
    }

    /**
     * @return static
     */
    public static function microsoftEdge()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::MICROSOFT_EDGE,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::WINDOWS,
        ]);
    }

    /**
     * @return static
     */
    public static function iphone()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::IPHONE,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::MAC,
        ]);
    }

    /**
     * @return static
     */
    public static function ipad()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::IPAD,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::MAC,
        ]);
    }

    /**
     * @return static
     */
    public static function opera()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::OPERA,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::ANY,
        ]);
    }

    /**
     * @return static
     */
    public static function safari()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::SAFARI,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::ANY,
        ]);
    }

    /**
     * @deprecated PhantomJS is no longer developed and its support will be removed in next major version.
     * Use headless Chrome or Firefox instead.
     * @return static
     */
    public static function phantomjs()
    {
        return new static([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::PHANTOMJS,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::ANY,
        ]);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return DesiredCapabilities
     */
    private function set($key, $value)
    {
        $this->capabilities[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function get($key, $default = null)
    {
        return isset($this->capabilities[$key])
            ? $this->capabilities[$key]
            : $default;
    }
}
