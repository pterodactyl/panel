<?php

namespace Facebook\WebDriver;

/**
 * The interface for WebDriver and WebDriverElement which is able to search for WebDriverElement inside.
 */
interface WebDriverSearchContext
{
    /**
     * Find the first WebDriverElement within this element using the given mechanism.
     *
     * @param WebDriverBy $locator
     * @return WebDriverElement NoSuchElementException is thrown in HttpCommandExecutor if no element is found.
     * @see WebDriverBy
     */
    public function findElement(WebDriverBy $locator);

    /**
     * Find all WebDriverElements within this element using the given mechanism.
     *
     * @param WebDriverBy $locator
     * @return WebDriverElement[] A list of all WebDriverElements, or an empty array if nothing matches
     * @see WebDriverBy
     */
    public function findElements(WebDriverBy $locator);
}
