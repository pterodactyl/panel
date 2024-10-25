<?php

namespace Facebook\WebDriver;

/**
 * The basic 8 mechanisms supported by webdriver to locate a web element.
 * ie. 'class name', 'css selector', 'id', 'name', 'link text',
 *     'partial link text', 'tag name' and 'xpath'.
 *
 * @see WebDriver::findElement, WebDriverElement::findElement
 */
class WebDriverBy
{
    /**
     * @var string
     */
    private $mechanism;
    /**
     * @var string
     */
    private $value;

    protected function __construct($mechanism, $value)
    {
        $this->mechanism = $mechanism;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getMechanism()
    {
        return $this->mechanism;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Locates elements whose class name contains the search value; compound class
     * names are not permitted.
     *
     * @param string $class_name
     * @return static
     */
    public static function className($class_name)
    {
        return new static('class name', $class_name);
    }

    /**
     * Locates elements matching a CSS selector.
     *
     * @param string $css_selector
     * @return static
     */
    public static function cssSelector($css_selector)
    {
        return new static('css selector', $css_selector);
    }

    /**
     * Locates elements whose ID attribute matches the search value.
     *
     * @param string $id
     * @return static
     */
    public static function id($id)
    {
        return new static('id', $id);
    }

    /**
     * Locates elements whose NAME attribute matches the search value.
     *
     * @param string $name
     * @return static
     */
    public static function name($name)
    {
        return new static('name', $name);
    }

    /**
     * Locates anchor elements whose visible text matches the search value.
     *
     * @param string $link_text
     * @return static
     */
    public static function linkText($link_text)
    {
        return new static('link text', $link_text);
    }

    /**
     * Locates anchor elements whose visible text partially matches the search
     * value.
     *
     * @param string $partial_link_text
     * @return static
     */
    public static function partialLinkText($partial_link_text)
    {
        return new static('partial link text', $partial_link_text);
    }

    /**
     * Locates elements whose tag name matches the search value.
     *
     * @param string $tag_name
     * @return static
     */
    public static function tagName($tag_name)
    {
        return new static('tag name', $tag_name);
    }

    /**
     * Locates elements matching an XPath expression.
     *
     * @param string $xpath
     * @return static
     */
    public static function xpath($xpath)
    {
        return new static('xpath', $xpath);
    }
}
