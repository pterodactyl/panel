<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\NoSuchFrameException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;

/**
 * Canned ExpectedConditions which are generally useful within webdriver tests.
 *
 * @see WebDriverWait
 */
class WebDriverExpectedCondition
{
    /**
     * A callable function to be executed by WebDriverWait. It should return
     * a truthy value, mostly boolean or a WebDriverElement, on success.
     * @var callable
     */
    private $apply;

    protected function __construct(callable $apply)
    {
        $this->apply = $apply;
    }

    /**
     * @return callable A callable function to be executed by WebDriverWait
     */
    public function getApply()
    {
        return $this->apply;
    }

    /**
     * An expectation for checking the title of a page.
     *
     * @param string $title The expected title, which must be an exact match.
     * @return static Condition returns whether current page title equals given string.
     */
    public static function titleIs($title)
    {
        return new static(
            function (WebDriver $driver) use ($title) {
                return $title === $driver->getTitle();
            }
        );
    }

    /**
     * An expectation for checking substring of a page Title.
     *
     * @param string $title The expected substring of Title.
     * @return static Condition returns whether current page title contains given string.
     */
    public static function titleContains($title)
    {
        return new static(
            function (WebDriver $driver) use ($title) {
                return mb_strpos($driver->getTitle(), $title) !== false;
            }
        );
    }

    /**
     * An expectation for checking current page title matches the given regular expression.
     *
     * @param string $titleRegexp The regular expression to test against.
     * @return static Condition returns whether current page title matches the regular expression.
     */
    public static function titleMatches($titleRegexp)
    {
        return new static(
            function (WebDriver $driver) use ($titleRegexp) {
                return (bool) preg_match($titleRegexp, $driver->getTitle());
            }
        );
    }

    /**
     * An expectation for checking the URL of a page.
     *
     * @param string $url The expected URL, which must be an exact match.
     * @return static Condition returns whether current URL equals given one.
     */
    public static function urlIs($url)
    {
        return new static(
            function (WebDriver $driver) use ($url) {
                return $url === $driver->getCurrentURL();
            }
        );
    }

    /**
     * An expectation for checking substring of the URL of a page.
     *
     * @param string $url The expected substring of the URL
     * @return static Condition returns whether current URL contains given string.
     */
    public static function urlContains($url)
    {
        return new static(
            function (WebDriver $driver) use ($url) {
                return mb_strpos($driver->getCurrentURL(), $url) !== false;
            }
        );
    }

    /**
     * An expectation for checking current page URL matches the given regular expression.
     *
     * @param string $urlRegexp The regular expression to test against.
     * @return static Condition returns whether current URL matches the regular expression.
     */
    public static function urlMatches($urlRegexp)
    {
        return new static(
            function (WebDriver $driver) use ($urlRegexp) {
                return (bool) preg_match($urlRegexp, $driver->getCurrentURL());
            }
        );
    }

    /**
     * An expectation for checking that an element is present on the DOM of a page.
     * This does not necessarily mean that the element is visible.
     *
     * @param WebDriverBy $by The locator used to find the element.
     * @return static Condition returns the WebDriverElement which is located.
     */
    public static function presenceOfElementLocated(WebDriverBy $by)
    {
        return new static(
            function (WebDriver $driver) use ($by) {
                try {
                    return $driver->findElement($by);
                } catch (NoSuchElementException $e) {
                    return false;
                }
            }
        );
    }

    /**
     * An expectation for checking that there is at least one element present on a web page.
     *
     * @param WebDriverBy $by The locator used to find the element.
     * @return static Condition return an array of WebDriverElement once they are located.
     */
    public static function presenceOfAllElementsLocatedBy(WebDriverBy $by)
    {
        return new static(
            function (WebDriver $driver) use ($by) {
                $elements = $driver->findElements($by);

                return count($elements) > 0 ? $elements : null;
            }
        );
    }

    /**
     * An expectation for checking that an element is present on the DOM of a page and visible.
     * Visibility means that the element is not only displayed but also has a height and width that is greater than 0.
     *
     * @param WebDriverBy $by The locator used to find the element.
     * @return static Condition returns the WebDriverElement which is located and visible.
     */
    public static function visibilityOfElementLocated(WebDriverBy $by)
    {
        return new static(
            function (WebDriver $driver) use ($by) {
                try {
                    $element = $driver->findElement($by);

                    return $element->isDisplayed() ? $element : null;
                } catch (StaleElementReferenceException $e) {
                    return null;
                }
            }
        );
    }

    /**
     * An expectation for checking than at least one element in an array of elements is present on the
     * DOM of a page and visible.
     * Visibility means that the element is not only displayed but also has a height and width that is greater than 0.
     *
     * @param WebDriverBy $by The located used to find the element.
     * @return static Condition returns the array of WebDriverElement that are located and visible.
     */
    public static function visibilityOfAnyElementLocated(WebDriverBy $by)
    {
        return new static(
            function (WebDriver $driver) use ($by) {
                $elements = $driver->findElements($by);
                $visibleElements = [];

                foreach ($elements as $element) {
                    try {
                        if ($element->isDisplayed()) {
                            $visibleElements[] = $element;
                        }
                    } catch (StaleElementReferenceException $e) {
                    }
                }

                return count($visibleElements) > 0 ? $visibleElements : null;
            }
        );
    }

    /**
     * An expectation for checking that an element, known to be present on the DOM of a page, is visible.
     * Visibility means that the element is not only displayed but also has a height and width that is greater than 0.
     *
     * @param WebDriverElement $element The element to be checked.
     * @return static Condition returns the same WebDriverElement once it is visible.
     */
    public static function visibilityOf(WebDriverElement $element)
    {
        return new static(
            function () use ($element) {
                return $element->isDisplayed() ? $element : null;
            }
        );
    }

    /**
     * An expectation for checking if the given text is present in the specified element.
     * To check exact text match use elementTextIs() condition.
     *
     * @codeCoverageIgnore
     * @deprecated Use WebDriverExpectedCondition::elementTextContains() instead
     * @param WebDriverBy $by The locator used to find the element.
     * @param string $text The text to be presented in the element.
     * @return static Condition returns whether the text is present in the element.
     */
    public static function textToBePresentInElement(WebDriverBy $by, $text)
    {
        return self::elementTextContains($by, $text);
    }

    /**
     * An expectation for checking if the given text is present in the specified element.
     * To check exact text match use elementTextIs() condition.
     *
     * @param WebDriverBy $by The locator used to find the element.
     * @param string $text The text to be presented in the element.
     * @return static Condition returns whether the partial text is present in the element.
     */
    public static function elementTextContains(WebDriverBy $by, $text)
    {
        return new static(
            function (WebDriver $driver) use ($by, $text) {
                try {
                    $element_text = $driver->findElement($by)->getText();

                    return mb_strpos($element_text, $text) !== false;
                } catch (StaleElementReferenceException $e) {
                    return null;
                }
            }
        );
    }

    /**
     * An expectation for checking if the given text exactly equals the text in specified element.
     * To check only partial substring of the text use elementTextContains() condition.
     *
     * @param WebDriverBy $by The locator used to find the element.
     * @param string $text The expected text of the element.
     * @return static Condition returns whether the element has text value equal to given one.
     */
    public static function elementTextIs(WebDriverBy $by, $text)
    {
        return new static(
            function (WebDriver $driver) use ($by, $text) {
                try {
                    return $driver->findElement($by)->getText() == $text;
                } catch (StaleElementReferenceException $e) {
                    return null;
                }
            }
        );
    }

    /**
     * An expectation for checking if the given regular expression matches the text in specified element.
     *
     * @param WebDriverBy $by The locator used to find the element.
     * @param string $regexp The regular expression to test against.
     * @return static Condition returns whether the element has text value equal to given one.
     */
    public static function elementTextMatches(WebDriverBy $by, $regexp)
    {
        return new static(
            function (WebDriver $driver) use ($by, $regexp) {
                try {
                    return (bool) preg_match($regexp, $driver->findElement($by)->getText());
                } catch (StaleElementReferenceException $e) {
                    return null;
                }
            }
        );
    }

    /**
     * An expectation for checking if the given text is present in the specified elements value attribute.
     *
     * @codeCoverageIgnore
     * @deprecated Use WebDriverExpectedCondition::elementValueContains() instead
     * @param WebDriverBy $by The locator used to find the element.
     * @param string $text The text to be presented in the element value.
     * @return static Condition returns whether the text is present in value attribute.
     */
    public static function textToBePresentInElementValue(WebDriverBy $by, $text)
    {
        return self::elementValueContains($by, $text);
    }

    /**
     * An expectation for checking if the given text is present in the specified elements value attribute.
     *
     * @param WebDriverBy $by The locator used to find the element.
     * @param string $text The text to be presented in the element value.
     * @return static Condition returns whether the text is present in value attribute.
     */
    public static function elementValueContains(WebDriverBy $by, $text)
    {
        return new static(
            function (WebDriver $driver) use ($by, $text) {
                try {
                    $element_text = $driver->findElement($by)->getAttribute('value');

                    return mb_strpos($element_text, $text) !== false;
                } catch (StaleElementReferenceException $e) {
                    return null;
                }
            }
        );
    }

    /**
     * Expectation for checking if iFrame exists. If iFrame exists switches driver's focus to the iFrame.
     *
     * @param string $frame_locator The locator used to find the iFrame
     *   expected to be either the id or name value of the i/frame
     * @return static Condition returns object focused on new frame when frame is found, false otherwise.
     */
    public static function frameToBeAvailableAndSwitchToIt($frame_locator)
    {
        return new static(
            function (WebDriver $driver) use ($frame_locator) {
                try {
                    return $driver->switchTo()->frame($frame_locator);
                } catch (NoSuchFrameException $e) {
                    return false;
                }
            }
        );
    }

    /**
     * An expectation for checking that an element is either invisible or not present on the DOM.
     *
     * @param WebDriverBy $by The locator used to find the element.
     * @return static Condition returns whether no visible element located.
     */
    public static function invisibilityOfElementLocated(WebDriverBy $by)
    {
        return new static(
            function (WebDriver $driver) use ($by) {
                try {
                    return !$driver->findElement($by)->isDisplayed();
                } catch (NoSuchElementException $e) {
                    return true;
                } catch (StaleElementReferenceException $e) {
                    return true;
                }
            }
        );
    }

    /**
     * An expectation for checking that an element with text is either invisible or not present on the DOM.
     *
     * @param WebDriverBy $by The locator used to find the element.
     * @param string $text The text of the element.
     * @return static Condition returns whether the text is found in the element located.
     */
    public static function invisibilityOfElementWithText(WebDriverBy $by, $text)
    {
        return new static(
            function (WebDriver $driver) use ($by, $text) {
                try {
                    return !($driver->findElement($by)->getText() === $text);
                } catch (NoSuchElementException $e) {
                    return true;
                } catch (StaleElementReferenceException $e) {
                    return true;
                }
            }
        );
    }

    /**
     * An expectation for checking an element is visible and enabled such that you can click it.
     *
     * @param WebDriverBy $by The locator used to find the element
     * @return static Condition return the WebDriverElement once it is located, visible and clickable.
     */
    public static function elementToBeClickable(WebDriverBy $by)
    {
        $visibility_of_element_located = self::visibilityOfElementLocated($by);

        return new static(
            function (WebDriver $driver) use ($visibility_of_element_located) {
                $element = call_user_func(
                    $visibility_of_element_located->getApply(),
                    $driver
                );

                try {
                    if ($element !== null && $element->isEnabled()) {
                        return $element;
                    }

                    return null;
                } catch (StaleElementReferenceException $e) {
                    return null;
                }
            }
        );
    }

    /**
     * Wait until an element is no longer attached to the DOM.
     *
     * @param WebDriverElement $element The element to wait for.
     * @return static Condition returns whether the element is still attached to the DOM.
     */
    public static function stalenessOf(WebDriverElement $element)
    {
        return new static(
            function () use ($element) {
                try {
                    $element->isEnabled();

                    return false;
                } catch (StaleElementReferenceException $e) {
                    return true;
                }
            }
        );
    }

    /**
     * Wrapper for a condition, which allows for elements to update by redrawing.
     *
     * This works around the problem of conditions which have two parts: find an element and then check for some
     * condition on it. For these conditions it is possible that an element is located and then subsequently it is
     * redrawn on the client. When this happens a StaleElementReferenceException is thrown when the second part of
     * the condition is checked.
     *
     * @param WebDriverExpectedCondition $condition The condition wrapped.
     * @return static Condition returns the return value of the getApply() of the given condition.
     */
    public static function refreshed(self $condition)
    {
        return new static(
            function (WebDriver $driver) use ($condition) {
                try {
                    return call_user_func($condition->getApply(), $driver);
                } catch (StaleElementReferenceException $e) {
                    return null;
                }
            }
        );
    }

    /**
     * An expectation for checking if the given element is selected.
     *
     * @param mixed $element_or_by Either the element or the locator.
     * @return static Condition returns whether the element is selected.
     */
    public static function elementToBeSelected($element_or_by)
    {
        return self::elementSelectionStateToBe(
            $element_or_by,
            true
        );
    }

    /**
     * An expectation for checking if the given element is selected.
     *
     * @param mixed $element_or_by Either the element or the locator.
     * @param bool $selected The required state.
     * @return static Condition returns whether the element is selected.
     */
    public static function elementSelectionStateToBe($element_or_by, $selected)
    {
        if ($element_or_by instanceof WebDriverElement) {
            return new static(
                function () use ($element_or_by, $selected) {
                    return $element_or_by->isSelected() === $selected;
                }
            );
        }

        if ($element_or_by instanceof WebDriverBy) {
            return new static(
                function (WebDriver $driver) use ($element_or_by, $selected) {
                    try {
                        $element = $driver->findElement($element_or_by);

                        return $element->isSelected() === $selected;
                    } catch (StaleElementReferenceException $e) {
                        return null;
                    }
                }
            );
        }

        throw new \InvalidArgumentException('Instance of either WebDriverElement or WebDriverBy must be given');
    }

    /**
     * An expectation for whether an alert() box is present.
     *
     * @return static Condition returns WebDriverAlert if alert() is present, null otherwise.
     */
    public static function alertIsPresent()
    {
        return new static(
            function (WebDriver $driver) {
                try {
                    // Unlike the Java code, we get a WebDriverAlert object regardless
                    // of whether there is an alert.  Calling getText() will throw
                    // an exception if it is not really there.
                    $alert = $driver->switchTo()->alert();
                    $alert->getText();

                    return $alert;
                } catch (NoSuchAlertException $e) {
                    return null;
                }
            }
        );
    }

    /**
     * An expectation checking the number of opened windows.
     *
     * @param int $expectedNumberOfWindows
     * @return static
     */
    public static function numberOfWindowsToBe($expectedNumberOfWindows)
    {
        return new static(
            function (WebDriver $driver) use ($expectedNumberOfWindows) {
                return count($driver->getWindowHandles()) == $expectedNumberOfWindows;
            }
        );
    }

    /**
     * An expectation with the logical opposite condition of the given condition.
     *
     * @param WebDriverExpectedCondition $condition The condition to be negated.
     * @return mixed The negation of the result of the given condition.
     */
    public static function not(self $condition)
    {
        return new static(
            function (WebDriver $driver) use ($condition) {
                $result = call_user_func($condition->getApply(), $driver);

                return !$result;
            }
        );
    }
}
