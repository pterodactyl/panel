<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\UnexpectedTagNameException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Support\XPathEscaper;

/**
 * Provides helper methods for checkboxes and radio buttons.
 */
abstract class AbstractWebDriverCheckboxOrRadio implements WebDriverSelectInterface
{
    /** @var WebDriverElement */
    protected $element;

    /** @var string */
    protected $type;

    /** @var string */
    protected $name;

    public function __construct(WebDriverElement $element)
    {
        $tagName = $element->getTagName();
        if ($tagName !== 'input') {
            throw new UnexpectedTagNameException('input', $tagName);
        }

        $this->name = $element->getAttribute('name');
        if ($this->name === null) {
            throw new WebDriverException('The input does not have a "name" attribute.');
        }

        $this->element = $element;
    }

    public function getOptions()
    {
        return $this->getRelatedElements();
    }

    public function getAllSelectedOptions()
    {
        $selectedElement = [];
        foreach ($this->getRelatedElements() as $element) {
            if ($element->isSelected()) {
                $selectedElement[] = $element;

                if (!$this->isMultiple()) {
                    return $selectedElement;
                }
            }
        }

        return $selectedElement;
    }

    public function getFirstSelectedOption()
    {
        foreach ($this->getRelatedElements() as $element) {
            if ($element->isSelected()) {
                return $element;
            }
        }

        throw new NoSuchElementException(
            sprintf('No %s are selected', $this->type === 'radio' ? 'radio buttons' : 'checkboxes')
        );
    }

    public function selectByIndex($index)
    {
        $this->byIndex($index);
    }

    public function selectByValue($value)
    {
        $this->byValue($value);
    }

    public function selectByVisibleText($text)
    {
        $this->byVisibleText($text);
    }

    public function selectByVisiblePartialText($text)
    {
        $this->byVisibleText($text, true);
    }

    /**
     * Selects or deselects a checkbox or a radio button by its value.
     *
     * @param string $value
     * @param bool $select
     * @throws NoSuchElementException
     */
    protected function byValue($value, $select = true)
    {
        $matched = false;
        foreach ($this->getRelatedElements($value) as $element) {
            $select ? $this->selectOption($element) : $this->deselectOption($element);
            if (!$this->isMultiple()) {
                return;
            }

            $matched = true;
        }

        if (!$matched) {
            throw new NoSuchElementException(
                sprintf('Cannot locate %s with value: %s', $this->type, $value)
            );
        }
    }

    /**
     * Selects or deselects a checkbox or a radio button by its index.
     *
     * @param int $index
     * @param bool $select
     * @throws NoSuchElementException
     */
    protected function byIndex($index, $select = true)
    {
        $elements = $this->getRelatedElements();
        if (!isset($elements[$index])) {
            throw new NoSuchElementException(sprintf('Cannot locate %s with index: %d', $this->type, $index));
        }

        $select ? $this->selectOption($elements[$index]) : $this->deselectOption($elements[$index]);
    }

    /**
     * Selects or deselects a checkbox or a radio button by its visible text.
     *
     * @param string $text
     * @param bool $partial
     * @param bool $select
     */
    protected function byVisibleText($text, $partial = false, $select = true)
    {
        foreach ($this->getRelatedElements() as $element) {
            $normalizeFilter = sprintf(
                $partial ? 'contains(normalize-space(.), %s)' : 'normalize-space(.) = %s',
                XPathEscaper::escapeQuotes($text)
            );

            $xpath = 'ancestor::label';
            $xpathNormalize = sprintf('%s[%s]', $xpath, $normalizeFilter);

            $id = $element->getAttribute('id');
            if ($id !== null) {
                $idFilter = sprintf('@for = %s', XPathEscaper::escapeQuotes($id));

                $xpath .= sprintf(' | //label[%s]', $idFilter);
                $xpathNormalize .= sprintf(' | //label[%s and %s]', $idFilter, $normalizeFilter);
            }

            try {
                $element->findElement(WebDriverBy::xpath($xpathNormalize));
            } catch (NoSuchElementException $e) {
                if ($partial) {
                    continue;
                }

                try {
                    // Since the mechanism of getting the text in xpath is not the same as
                    // webdriver, use the expensive getText() to check if nothing is matched.
                    if ($text !== $element->findElement(WebDriverBy::xpath($xpath))->getText()) {
                        continue;
                    }
                } catch (NoSuchElementException $e) {
                    continue;
                }
            }

            $select ? $this->selectOption($element) : $this->deselectOption($element);
            if (!$this->isMultiple()) {
                return;
            }
        }
    }

    /**
     * Gets checkboxes or radio buttons with the same name.
     *
     * @param string|null $value
     * @return WebDriverElement[]
     */
    protected function getRelatedElements($value = null)
    {
        $valueSelector = $value ? sprintf(' and @value = %s', XPathEscaper::escapeQuotes($value)) : '';
        $formId = $this->element->getAttribute('form');
        if ($formId === null) {
            $form = $this->element->findElement(WebDriverBy::xpath('ancestor::form'));

            $formId = $form->getAttribute('id');
            if ($formId === '' || $formId === null) {
                return $form->findElements(WebDriverBy::xpath(
                    sprintf('.//input[@name = %s%s]', XPathEscaper::escapeQuotes($this->name), $valueSelector)
                ));
            }
        }

        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#form
        return $this->element->findElements(
            WebDriverBy::xpath(sprintf(
                '//form[@id = %1$s]//input[@name = %2$s%3$s'
                . ' and ((boolean(@form) = true() and @form = %1$s) or boolean(@form) = false())]'
                . ' | //input[@form = %1$s and @name = %2$s%3$s]',
                XPathEscaper::escapeQuotes($formId),
                XPathEscaper::escapeQuotes($this->name),
                $valueSelector
            ))
        );
    }

    /**
     * Selects a checkbox or a radio button.
     */
    protected function selectOption(WebDriverElement $element)
    {
        if (!$element->isSelected()) {
            $element->click();
        }
    }

    /**
     * Deselects a checkbox or a radio button.
     */
    protected function deselectOption(WebDriverElement $element)
    {
        if ($element->isSelected()) {
            $element->click();
        }
    }
}
