<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\UnexpectedTagNameException;
use Facebook\WebDriver\Exception\UnsupportedOperationException;
use Facebook\WebDriver\Support\XPathEscaper;

/**
 * Models a default HTML `<select>` tag, providing helper methods to select and deselect options.
 */
class WebDriverSelect implements WebDriverSelectInterface
{
    /** @var WebDriverElement */
    private $element;
    /** @var bool */
    private $isMulti;

    public function __construct(WebDriverElement $element)
    {
        $tag_name = $element->getTagName();

        if ($tag_name !== 'select') {
            throw new UnexpectedTagNameException('select', $tag_name);
        }
        $this->element = $element;
        $value = $element->getAttribute('multiple');
        $this->isMulti = $value === 'true';
    }

    public function isMultiple()
    {
        return $this->isMulti;
    }

    public function getOptions()
    {
        return $this->element->findElements(WebDriverBy::tagName('option'));
    }

    public function getAllSelectedOptions()
    {
        $selected_options = [];
        foreach ($this->getOptions() as $option) {
            if ($option->isSelected()) {
                $selected_options[] = $option;

                if (!$this->isMultiple()) {
                    return $selected_options;
                }
            }
        }

        return $selected_options;
    }

    public function getFirstSelectedOption()
    {
        foreach ($this->getOptions() as $option) {
            if ($option->isSelected()) {
                return $option;
            }
        }

        throw new NoSuchElementException('No options are selected');
    }

    public function selectByIndex($index)
    {
        foreach ($this->getOptions() as $option) {
            if ($option->getAttribute('index') === (string) $index) {
                $this->selectOption($option);

                return;
            }
        }

        throw new NoSuchElementException(sprintf('Cannot locate option with index: %d', $index));
    }

    public function selectByValue($value)
    {
        $matched = false;
        $xpath = './/option[@value = ' . XPathEscaper::escapeQuotes($value) . ']';
        $options = $this->element->findElements(WebDriverBy::xpath($xpath));

        foreach ($options as $option) {
            $this->selectOption($option);
            if (!$this->isMultiple()) {
                return;
            }
            $matched = true;
        }

        if (!$matched) {
            throw new NoSuchElementException(
                sprintf('Cannot locate option with value: %s', $value)
            );
        }
    }

    public function selectByVisibleText($text)
    {
        $matched = false;
        $xpath = './/option[normalize-space(.) = ' . XPathEscaper::escapeQuotes($text) . ']';
        $options = $this->element->findElements(WebDriverBy::xpath($xpath));

        foreach ($options as $option) {
            $this->selectOption($option);
            if (!$this->isMultiple()) {
                return;
            }
            $matched = true;
        }

        // Since the mechanism of getting the text in xpath is not the same as
        // webdriver, use the expensive getText() to check if nothing is matched.
        if (!$matched) {
            foreach ($this->getOptions() as $option) {
                if ($option->getText() === $text) {
                    $this->selectOption($option);
                    if (!$this->isMultiple()) {
                        return;
                    }
                    $matched = true;
                }
            }
        }

        if (!$matched) {
            throw new NoSuchElementException(
                sprintf('Cannot locate option with text: %s', $text)
            );
        }
    }

    public function selectByVisiblePartialText($text)
    {
        $matched = false;
        $xpath = './/option[contains(normalize-space(.), ' . XPathEscaper::escapeQuotes($text) . ')]';
        $options = $this->element->findElements(WebDriverBy::xpath($xpath));

        foreach ($options as $option) {
            $this->selectOption($option);
            if (!$this->isMultiple()) {
                return;
            }
            $matched = true;
        }

        if (!$matched) {
            throw new NoSuchElementException(
                sprintf('Cannot locate option with text: %s', $text)
            );
        }
    }

    public function deselectAll()
    {
        if (!$this->isMultiple()) {
            throw new UnsupportedOperationException('You may only deselect all options of a multi-select');
        }

        foreach ($this->getOptions() as $option) {
            $this->deselectOption($option);
        }
    }

    public function deselectByIndex($index)
    {
        if (!$this->isMultiple()) {
            throw new UnsupportedOperationException('You may only deselect options of a multi-select');
        }

        foreach ($this->getOptions() as $option) {
            if ($option->getAttribute('index') === (string) $index) {
                $this->deselectOption($option);

                return;
            }
        }
    }

    public function deselectByValue($value)
    {
        if (!$this->isMultiple()) {
            throw new UnsupportedOperationException('You may only deselect options of a multi-select');
        }

        $xpath = './/option[@value = ' . XPathEscaper::escapeQuotes($value) . ']';
        $options = $this->element->findElements(WebDriverBy::xpath($xpath));
        foreach ($options as $option) {
            $this->deselectOption($option);
        }
    }

    public function deselectByVisibleText($text)
    {
        if (!$this->isMultiple()) {
            throw new UnsupportedOperationException('You may only deselect options of a multi-select');
        }

        $xpath = './/option[normalize-space(.) = ' . XPathEscaper::escapeQuotes($text) . ']';
        $options = $this->element->findElements(WebDriverBy::xpath($xpath));
        foreach ($options as $option) {
            $this->deselectOption($option);
        }
    }

    public function deselectByVisiblePartialText($text)
    {
        if (!$this->isMultiple()) {
            throw new UnsupportedOperationException('You may only deselect options of a multi-select');
        }

        $xpath = './/option[contains(normalize-space(.), ' . XPathEscaper::escapeQuotes($text) . ')]';
        $options = $this->element->findElements(WebDriverBy::xpath($xpath));
        foreach ($options as $option) {
            $this->deselectOption($option);
        }
    }

    /**
     * Mark option selected
     * @param WebDriverElement $option
     */
    protected function selectOption(WebDriverElement $option)
    {
        if (!$option->isSelected()) {
            $option->click();
        }
    }

    /**
     * Mark option not selected
     * @param WebDriverElement $option
     */
    protected function deselectOption(WebDriverElement $option)
    {
        if ($option->isSelected()) {
            $option->click();
        }
    }
}
