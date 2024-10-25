<?php

namespace Facebook\WebDriver\Exception;

/**
 * A command could not be completed because the element is in an invalid state, e.g. attempting to clear an element
 * that isn’t both editable and resettable.
 */
class InvalidElementStateException extends WebDriverException
{
}
