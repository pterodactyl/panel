<?php

namespace Facebook\WebDriver\Firefox;

use ReturnTypeWillChange;

/**
 * Class to manage Firefox-specific capabilities
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/WebDriver/Capabilities/firefoxOptions
 */
class FirefoxOptions implements \JsonSerializable
{
    /** @var string The key of FirefoxOptions in desired capabilities */
    const CAPABILITY = 'moz:firefoxOptions';
    /** @var string */
    const OPTION_ARGS = 'args';
    /** @var string */
    const OPTION_PREFS = 'prefs';
    /** @var string */
    const OPTION_PROFILE = 'profile';

    /** @var array */
    private $options = [];
    /** @var array */
    private $arguments = [];
    /** @var array */
    private $preferences = [];
    /** @var FirefoxProfile */
    private $profile;

    public function __construct()
    {
        // Set default preferences:
        // disable the "Reader View" help tooltip, which can hide elements in the window.document
        $this->setPreference(FirefoxPreferences::READER_PARSE_ON_LOAD_ENABLED, false);
        // disable JSON viewer and let JSON be rendered as raw data
        $this->setPreference(FirefoxPreferences::DEVTOOLS_JSONVIEW, false);
    }

    /**
     * Directly set firefoxOptions.
     * Use `addArguments` to add command line arguments and `setPreference` to set Firefox about:config entry.
     *
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setOption($name, $value)
    {
        if ($name === self::OPTION_PREFS) {
            throw new \InvalidArgumentException('Use setPreference() method to set Firefox preferences');
        }
        if ($name === self::OPTION_ARGS) {
            throw new \InvalidArgumentException('Use addArguments() method to add Firefox arguments');
        }
        if ($name === self::OPTION_PROFILE) {
            throw new \InvalidArgumentException('Use setProfile() method to set Firefox profile');
        }

        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Command line arguments to pass to the Firefox binary.
     * These must include the leading dash (-) where required, e.g. ['-headless'].
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/WebDriver/Capabilities/firefoxOptions#args
     * @param string[] $arguments
     * @return self
     */
    public function addArguments(array $arguments)
    {
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * Set Firefox preference (about:config entry).
     *
     * @see http://kb.mozillazine.org/About:config_entries
     * @see https://developer.mozilla.org/en-US/docs/Web/WebDriver/Capabilities/firefoxOptions#prefs
     * @param string $name
     * @param string|bool|int $value
     * @return self
     */
    public function setPreference($name, $value)
    {
        $this->preferences[$name] = $value;

        return $this;
    }

    /**
     * @see https://github.com/php-webdriver/php-webdriver/wiki/Firefox#firefox-profile
     * @param FirefoxProfile $profile
     * @return self
     */
    public function setProfile(FirefoxProfile $profile)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = $this->options;
        if (!empty($this->arguments)) {
            $array[self::OPTION_ARGS] = $this->arguments;
        }
        if (!empty($this->preferences)) {
            $array[self::OPTION_PREFS] = $this->preferences;
        }
        if (!empty($this->profile)) {
            $array[self::OPTION_PROFILE] = $this->profile->encode();
        }

        return $array;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return new \ArrayObject($this->toArray());
    }
}
