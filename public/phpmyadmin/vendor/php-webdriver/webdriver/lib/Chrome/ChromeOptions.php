<?php

namespace Facebook\WebDriver\Chrome;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use JsonSerializable;
use ReturnTypeWillChange;

/**
 * The class manages the capabilities in ChromeDriver.
 *
 * @see https://sites.google.com/a/chromium.org/chromedriver/capabilities
 */
class ChromeOptions implements JsonSerializable
{
    /**
     * The key of chromeOptions in desired capabilities (in legacy OSS JsonWire protocol)
     * @todo Replace value with 'goog:chromeOptions' after JsonWire protocol support is removed
     */
    const CAPABILITY = 'chromeOptions';
    /**
     * The key of chromeOptions in desired capabilities (in W3C compatible protocol)
     */
    const CAPABILITY_W3C = 'goog:chromeOptions';
    /**
     * @var array
     */
    private $arguments = [];
    /**
     * @var string
     */
    private $binary = '';
    /**
     * @var array
     */
    private $extensions = [];
    /**
     * @var array
     */
    private $experimentalOptions = [];

    /**
     * Return a version of the class which can JSON serialized.
     *
     * @return array
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Sets the path of the Chrome executable. The path should be either absolute
     * or relative to the location running ChromeDriver server.
     *
     * @param string $path
     * @return ChromeOptions
     */
    public function setBinary($path)
    {
        $this->binary = $path;

        return $this;
    }

    /**
     * @param array $arguments
     * @return ChromeOptions
     */
    public function addArguments(array $arguments)
    {
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * Add a Chrome extension to install on browser startup. Each path should be
     * a packed Chrome extension.
     *
     * @param array $paths
     * @return ChromeOptions
     */
    public function addExtensions(array $paths)
    {
        foreach ($paths as $path) {
            $this->addExtension($path);
        }

        return $this;
    }

    /**
     * @param array $encoded_extensions An array of base64 encoded of the extensions.
     * @return ChromeOptions
     */
    public function addEncodedExtensions(array $encoded_extensions)
    {
        foreach ($encoded_extensions as $encoded_extension) {
            $this->addEncodedExtension($encoded_extension);
        }

        return $this;
    }

    /**
     * Sets an experimental option which has not exposed officially.
     *
     * When using "prefs" to set Chrome preferences, please be aware they are so far not supported by
     * Chrome running in headless mode, see https://bugs.chromium.org/p/chromium/issues/detail?id=775911
     *
     * @param string $name
     * @param mixed $value
     * @return ChromeOptions
     */
    public function setExperimentalOption($name, $value)
    {
        $this->experimentalOptions[$name] = $value;

        return $this;
    }

    /**
     * @return DesiredCapabilities The DesiredCapabilities for Chrome with this options.
     */
    public function toCapabilities()
    {
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(self::CAPABILITY, $this);

        return $capabilities;
    }

    /**
     * @return \ArrayObject|array
     */
    public function toArray()
    {
        // The selenium server expects a 'dictionary' instead of a 'list' when
        // reading the chrome option. However, an empty array in PHP will be
        // converted to a 'list' instead of a 'dictionary'. To fix it, we work
        // with `ArrayObject`
        $options = new \ArrayObject($this->experimentalOptions);

        if (!empty($this->binary)) {
            $options['binary'] = $this->binary;
        }

        if (!empty($this->arguments)) {
            $options['args'] = $this->arguments;
        }

        if (!empty($this->extensions)) {
            $options['extensions'] = $this->extensions;
        }

        return $options;
    }

    /**
     * Add a Chrome extension to install on browser startup. Each path should be a
     * packed Chrome extension.
     *
     * @param string $path
     * @return ChromeOptions
     */
    private function addExtension($path)
    {
        $this->addEncodedExtension(base64_encode(file_get_contents($path)));

        return $this;
    }

    /**
     * @param string $encoded_extension Base64 encoded of the extension.
     * @return ChromeOptions
     */
    private function addEncodedExtension($encoded_extension)
    {
        $this->extensions[] = $encoded_extension;

        return $this;
    }
}
