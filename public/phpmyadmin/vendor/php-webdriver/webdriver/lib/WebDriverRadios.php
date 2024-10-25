<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Exception\UnsupportedOperationException;
use Facebook\WebDriver\Exception\WebDriverException;

/**
 * Provides helper methods for radio buttons.
 */
class WebDriverRadios extends AbstractWebDriverCheckboxOrRadio
{
    public function __construct(WebDriverElement $element)
    {
        parent::__construct($element);

        $this->type = $element->getAttribute('type');
        if ($this->type !== 'radio') {
            throw new WebDriverException('The input must be of type "radio".');
        }
    }

    public function isMultiple()
    {
        return false;
    }

    public function deselectAll()
    {
        throw new UnsupportedOperationException('You cannot deselect radio buttons');
    }

    public function deselectByIndex($index)
    {
        throw new UnsupportedOperationException('You cannot deselect radio buttons');
    }

    public function deselectByValue($value)
    {
        throw new UnsupportedOperationException('You cannot deselect radio buttons');
    }

    public function deselectByVisibleText($text)
    {
        throw new UnsupportedOperationException('You cannot deselect radio buttons');
    }

    public function deselectByVisiblePartialText($text)
    {
        throw new UnsupportedOperationException('You cannot deselect radio buttons');
    }
}
