<?php

namespace Facebook\WebDriver;

use InvalidArgumentException;

/**
 * Set values of an cookie.
 *
 * Implements ArrayAccess for backwards compatibility.
 *
 * @see https://w3c.github.io/webdriver/webdriver-spec.html#cookies
 */
class Cookie implements \ArrayAccess
{
    /** @var array */
    protected $cookie = [];

    /**
     * @param string $name The name of the cookie; may not be null or an empty string.
     * @param string $value The cookie value; may not be null.
     */
    public function __construct($name, $value)
    {
        $this->validateCookieName($name);
        $this->validateCookieValue($value);

        $this->cookie['name'] = $name;
        $this->cookie['value'] = $value;
    }

    /**
     * @param array $cookieArray The cookie fields; must contain name and value.
     * @return Cookie
     */
    public static function createFromArray(array $cookieArray)
    {
        if (!isset($cookieArray['name'])) {
            throw new InvalidArgumentException('Cookie name should be set');
        }
        if (!isset($cookieArray['value'])) {
            throw new InvalidArgumentException('Cookie value should be set');
        }
        $cookie = new self($cookieArray['name'], $cookieArray['value']);

        if (isset($cookieArray['path'])) {
            $cookie->setPath($cookieArray['path']);
        }
        if (isset($cookieArray['domain'])) {
            $cookie->setDomain($cookieArray['domain']);
        }
        if (isset($cookieArray['expiry'])) {
            $cookie->setExpiry($cookieArray['expiry']);
        }
        if (isset($cookieArray['secure'])) {
            $cookie->setSecure($cookieArray['secure']);
        }
        if (isset($cookieArray['httpOnly'])) {
            $cookie->setHttpOnly($cookieArray['httpOnly']);
        }
        if (isset($cookieArray['sameSite'])) {
            $cookie->setSameSite($cookieArray['sameSite']);
        }

        return $cookie;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->offsetGet('name');
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->offsetGet('value');
    }

    /**
     * The path the cookie is visible to. Defaults to "/" if omitted.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->offsetSet('path', $path);
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->offsetGet('path');
    }

    /**
     * The domain the cookie is visible to. Defaults to the current browsing context's document's URL domain if omitted.
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
        if (mb_strpos($domain, ':') !== false) {
            throw new InvalidArgumentException(sprintf('Cookie domain "%s" should not contain a port', $domain));
        }

        $this->offsetSet('domain', $domain);
    }

    /**
     * @return string|null
     */
    public function getDomain()
    {
        return $this->offsetGet('domain');
    }

    /**
     * The cookie's expiration date, specified in seconds since Unix Epoch.
     *
     * @param int $expiry
     */
    public function setExpiry($expiry)
    {
        $this->offsetSet('expiry', (int) $expiry);
    }

    /**
     * @return int|null
     */
    public function getExpiry()
    {
        return $this->offsetGet('expiry');
    }

    /**
     * Whether this cookie requires a secure connection (https). Defaults to false if omitted.
     *
     * @param bool $secure
     */
    public function setSecure($secure)
    {
        $this->offsetSet('secure', $secure);
    }

    /**
     * @return bool|null
     */
    public function isSecure()
    {
        return $this->offsetGet('secure');
    }

    /**
     * Whether the cookie is an HTTP only cookie. Defaults to false if omitted.
     *
     * @param bool $httpOnly
     */
    public function setHttpOnly($httpOnly)
    {
        $this->offsetSet('httpOnly', $httpOnly);
    }

    /**
     * @return bool|null
     */
    public function isHttpOnly()
    {
        return $this->offsetGet('httpOnly');
    }

    /**
     * The cookie's same-site value.
     *
     * @param string $sameSite
     */
    public function setSameSite($sameSite)
    {
        $this->offsetSet('sameSite', $sameSite);
    }

    /**
     * @return string|null
     */
    public function getSameSite()
    {
        return $this->offsetGet('sameSite');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $cookie = $this->cookie;
        if (!isset($cookie['secure'])) {
            // Passing a boolean value for the "secure" flag is mandatory when using geckodriver
            $cookie['secure'] = false;
        }

        return $cookie;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->cookie[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->cookie[$offset] : null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if ($value === null) {
            unset($this->cookie[$offset]);
        } else {
            $this->cookie[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->cookie[$offset]);
    }

    /**
     * @param string $name
     */
    protected function validateCookieName($name)
    {
        if ($name === null || $name === '') {
            throw new InvalidArgumentException('Cookie name should be non-empty');
        }

        if (mb_strpos($name, ';') !== false) {
            throw new InvalidArgumentException('Cookie name should not contain a ";"');
        }
    }

    /**
     * @param string $value
     */
    protected function validateCookieValue($value)
    {
        if ($value === null) {
            throw new InvalidArgumentException('Cookie value is required when setting a cookie');
        }
    }
}
