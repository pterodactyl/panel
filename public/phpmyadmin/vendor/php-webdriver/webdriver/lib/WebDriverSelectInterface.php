<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\UnsupportedOperationException;

/**
 * Models an element of select type, providing helper methods to select and deselect options.
 */
interface WebDriverSelectInterface
{
    /**
     * @return bool Whether this select element support selecting multiple options.
     */
    public function isMultiple();

    /**
     * @return WebDriverElement[] All options belonging to this select tag.
     */
    public function getOptions();

    /**
     * @return WebDriverElement[] All selected options belonging to this select tag.
     */
    public function getAllSelectedOptions();

    /**
     * @throws NoSuchElementException
     *
     * @return WebDriverElement The first selected option in this select tag (or the currently selected option in a
     * normal select)
     */
    public function getFirstSelectedOption();

    /**
     * Select the option at the given index.
     *
     * @param int $index The index of the option. (0-based)
     *
     * @throws NoSuchElementException
     */
    public function selectByIndex($index);

    /**
     * Select all options that have value attribute matching the argument. That is, when given "foo" this would
     * select an option like:
     *
     * `<option value="foo">Bar</option>`
     *
     * @param string $value The value to match against.
     *
     * @throws NoSuchElementException
     */
    public function selectByValue($value);

    /**
     * Select all options that display text matching the argument. That is, when given "Bar" this would
     * select an option like:
     *
     * `<option value="foo">Bar</option>`
     *
     * @param string $text The visible text to match against.
     *
     * @throws NoSuchElementException
     */
    public function selectByVisibleText($text);

    /**
     * Select all options that display text partially matching the argument. That is, when given "Bar" this would
     * select an option like:
     *
     * `<option value="bar">Foo Bar Baz</option>`
     *
     * @param string $text The visible text to match against.
     *
     * @throws NoSuchElementException
     */
    public function selectByVisiblePartialText($text);

    /**
     * Deselect all options in multiple select tag.
     *
     * @throws UnsupportedOperationException If the SELECT does not support multiple selections
     */
    public function deselectAll();

    /**
     * Deselect the option at the given index.
     *
     * @param int $index The index of the option. (0-based)
     * @throws UnsupportedOperationException If the SELECT does not support multiple selections
     */
    public function deselectByIndex($index);

    /**
     * Deselect all options that have value attribute matching the argument. That is, when given "foo" this would
     * deselect an option like:
     *
     * `<option value="foo">Bar</option>`
     *
     * @param string $value The value to match against.
     * @throws UnsupportedOperationException If the SELECT does not support multiple selections
     */
    public function deselectByValue($value);

    /**
     * Deselect all options that display text matching the argument. That is, when given "Bar" this would
     * deselect an option like:
     *
     * `<option value="foo">Bar</option>`
     *
     * @param string $text The visible text to match against.
     * @throws UnsupportedOperationException If the SELECT does not support multiple selections
     */
    public function deselectByVisibleText($text);

    /**
     * Deselect all options that display text matching the argument. That is, when given "Bar" this would
     * deselect an option like:
     *
     * `<option value="foo">Foo Bar Baz</option>`
     *
     * @param string $text The visible text to match against.
     * @throws UnsupportedOperationException If the SELECT does not support multiple selections
     */
    public function deselectByVisiblePartialText($text);
}
