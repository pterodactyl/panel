<?php

namespace Facebook\WebDriver\Exception;

/**
 * A command failed because the referenced shadow root is no longer attached to the DOM.
 */
class DetachedShadowRootException extends WebDriverException
{
}
